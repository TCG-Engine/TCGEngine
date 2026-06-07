# Prereq:
# pip install opencv-python pillow numpy
#
# Usage:
# python convert-mov-to-transparent-webp.py

import cv2
import numpy as np
from PIL import Image

input_path = r"C:\Users\maxim\Downloads\omen-counter.mov"
output_path = r"C:\Users\maxim\Downloads\omen-counter.webp"

TARGET_FPS = 8  # output frames per second
FRAME_SAMPLE_STEP = 1  # keep every Nth source frame (overrides TARGET_FPS when > 1)
FAST_PLAYBACK = False  # True: sampled frames keep source frame duration (speeds up animation)
RESPECT_TARGET_FPS = True  # In FAST_PLAYBACK mode, drop extra sampled frames to match TARGET_FPS without increasing duration
BOARD_MODE = False  # When True: skip all transparency/crop/border - just resample FPS and convert to WebP
VALUE_SCALE = 128.0  # scales HSV V per pixel before gamma mapping
ALPHA_GAMMA = 2.0  # lower values push more pixels toward opacity
ALPHA_SCALE = 4.0  # >1.0 boosts alpha, <1.0 reduces alpha
SATURATION_SCALE = 1.0  # >1.0 boosts color saturation, 1.0 = no change

# --- Border mode ---
# When True, draws a solid-color outline around the visible content in each frame.
BORDER_ENABLED = False
BORDER_SIZE = 4           # outline thickness in pixels
BORDER_COLOR = (0, 0, 0, 255)  # RGBA - black by default

# --- Crop mode ---
# When True, scans all sampled frames to find the bounding box of all non-black
# pixels (unioned across every frame), then crops every frame to that box before
# resizing. Ideal for icon animations recorded on a black background.
CROP_MODE = True
CROP_PADDING = 0        # extra pixels added on every side of the detected bounds
BLACK_THRESHOLD = 60     # HSV V values (0-255) at or below this count as "black"
OUTPUT_SIZE = (60, 60)


def compute_crop_bounds(raw_frames, threshold, padding):
    """Return (x0, y0, x1, y1) - the union bbox of all non-black pixels across
    every frame in raw_frames (list of BGR numpy arrays), expanded by padding."""
    h, w = raw_frames[0].shape[:2]
    x0, y0, x1, y1 = w, h, 0, 0

    for bgr in raw_frames:
        hsv = cv2.cvtColor(bgr, cv2.COLOR_BGR2HSV)
        v = hsv[:, :, 2]
        mask = v > threshold
        rows = np.any(mask, axis=1)
        cols = np.any(mask, axis=0)
        if not rows.any():
            continue  # fully black frame - skip
        r0, r1 = np.argmax(rows), h - 1 - np.argmax(rows[::-1])
        c0, c1 = np.argmax(cols), w - 1 - np.argmax(cols[::-1])
        x0 = min(x0, c0)
        y0 = min(y0, r0)
        x1 = max(x1, c1)
        y1 = max(y1, r1)

    if x1 <= x0 or y1 <= y0:
        return (0, 0, w, h)  # nothing found - use full frame

    x0 = max(0, x0 - padding)
    y0 = max(0, y0 - padding)
    x1 = min(w, x1 + padding + 1)
    y1 = min(h, y1 + padding + 1)
    return (x0, y0, x1, y1)


def bgr_to_rgba(bgr):
    """Convert a BGR frame to an RGBA numpy array using the HSV-value alpha mapping."""
    hsv = cv2.cvtColor(bgr, cv2.COLOR_BGR2HSV).astype(np.float32)
    hsv[:, :, 1] = np.clip(hsv[:, :, 1] * SATURATION_SCALE, 0, 255)
    hsv_boosted = hsv.astype(np.uint8)
    rgb = cv2.cvtColor(hsv_boosted, cv2.COLOR_HSV2RGB)
    v = hsv[:, :, 2] / 255.0
    v = np.clip(v * VALUE_SCALE, 0.0, 1.0)
    alpha_linear = np.power(v, ALPHA_GAMMA) * 255.0
    alpha = np.clip(alpha_linear * ALPHA_SCALE, 0, 255).astype(np.uint8)
    return np.dstack((rgb, alpha))


def add_content_border(img: Image.Image) -> Image.Image:
    """Draw a BORDER_COLOR outline of BORDER_SIZE pixels around the opaque
    content in an RGBA image by dilating the alpha mask."""
    rgba = np.array(img)
    alpha = rgba[:, :, 3]
    kernel = cv2.getStructuringElement(
        cv2.MORPH_ELLIPSE, (2 * BORDER_SIZE + 1, 2 * BORDER_SIZE + 1)
    )
    dilated = cv2.dilate(alpha, kernel)
    border_mask = (dilated > 0) & (alpha == 0)
    rgba[border_mask] = BORDER_COLOR
    return Image.fromarray(rgba, "RGBA")


def even_sample_frames(frames, target_count):
    """Keep target_count frames, evenly distributed across the input sequence."""
    if target_count >= len(frames):
        return frames
    if target_count <= 1:
        return [frames[0]]
    idxs = np.linspace(0, len(frames) - 1, target_count).round().astype(int)
    return [frames[i] for i in idxs]


cap = cv2.VideoCapture(input_path)
source_fps = cap.get(cv2.CAP_PROP_FPS) or 24
if FRAME_SAMPLE_STEP and FRAME_SAMPLE_STEP > 1:
    keep_every = int(FRAME_SAMPLE_STEP)
else:
    keep_every = max(1, round(source_fps / TARGET_FPS))

if FAST_PLAYBACK:
    # Keep sampled frames at the original source frame duration.
    # This speeds up playback by approximately keep_every times.
    frame_duration_ms = max(1, int(1000 / source_fps))
else:
    # Preserve original motion speed after sampling.
    frame_duration_ms = max(1, int(keep_every * 1000 / source_fps))

output_fps = 1000 / frame_duration_ms
speed_multiplier = keep_every if FAST_PLAYBACK else 1
print(
    f"Source: {source_fps:.2f} fps - keeping every {keep_every} frame(s) -> "
    f"{output_fps:.1f} fps output ({frame_duration_ms} ms/frame), "
    f"speed x{speed_multiplier:.2f}"
)

# --- Pass 1: collect sampled raw BGR frames ---
raw_frames = []
frame_index = 0
while True:
    ret, frame = cap.read()
    if not ret:
        break
    if frame_index % keep_every == 0:
        raw_frames.append(frame)
    frame_index += 1

cap.release()

if not raw_frames:
    print("ERROR: No frames were read from the video.")
else:
    if FAST_PLAYBACK and RESPECT_TARGET_FPS:
        fast_duration_s = len(raw_frames) / source_fps
        target_count = max(1, int(round(fast_duration_s * TARGET_FPS)))
        original_count = len(raw_frames)
        raw_frames = even_sample_frames(raw_frames, target_count)
        frame_duration_ms = max(1, int(1000 / TARGET_FPS))
        print(
            f"Target FPS clamp: {original_count} -> {len(raw_frames)} frame(s) "
            f"at {TARGET_FPS} fps over ~{fast_duration_s:.2f}s"
        )

    if BOARD_MODE:
        # --- Board mode: no transparency, cropping, or effects ---
        frames = []
        for bgr in raw_frames:
            rgb = cv2.cvtColor(bgr, cv2.COLOR_BGR2RGB)
            img = Image.fromarray(rgb, "RGB")
            frames.append(img)
    else:
        # --- Crop mode: compute union bounds across all sampled frames ---
        crop_box = None
        if CROP_MODE:
            crop_box = compute_crop_bounds(raw_frames, BLACK_THRESHOLD, CROP_PADDING)
            x0, y0, x1, y1 = crop_box
            print(f"Crop mode: detected bounds ({x0}, {y0}) -> ({x1}, {y1})  [{x1-x0}x{y1-y0} px]")

        # --- Pass 2: convert to RGBA, optionally crop, resize ---
        frames = []
        for bgr in raw_frames:
            rgba = bgr_to_rgba(bgr)
            img = Image.fromarray(rgba, "RGBA")
            if crop_box is not None:
                x0, y0, x1, y1 = crop_box
                img = img.crop((x0, y0, x1, y1))
            img = img.resize(OUTPUT_SIZE, Image.Resampling.LANCZOS)
            if BORDER_ENABLED:
                img = add_content_border(img)
            frames.append(img)

    print(f"Writing {len(frames)} frames to {output_path} ...")
    frames[0].save(
        output_path,
        save_all=True,
        append_images=frames[1:],
        duration=frame_duration_ms,
        loop=0,
        format="WEBP",
        quality=80,
        method=4,
    )
    print("Done.")
