# Prereq:
# pip install opencv-python pillow numpy
#
# Usage:
# python convert-mov-to-transparent-webp.py

import cv2
import numpy as np
from PIL import Image

input_path = r"C:\Users\maxim\Downloads\smoke-overlay-vid.mov"
output_path = r"C:\Users\maxim\Downloads\smoke-overlay.webp"

TARGET_FPS = 8  # output frames per second
VALUE_SCALE = 2.0  # scales HSV V per pixel before gamma mapping
ALPHA_GAMMA = 0.4  # lower values push more pixels toward opacity
ALPHA_SCALE = 1.0  # >1.0 boosts alpha, <1.0 reduces alpha

cap = cv2.VideoCapture(input_path)
source_fps = cap.get(cv2.CAP_PROP_FPS) or 24
keep_every = max(1, round(source_fps / TARGET_FPS))
# Each kept frame covers exactly keep_every source frames, so its display
# duration must span that many source frame intervals to preserve motion speed.
frame_duration_ms = int(keep_every * 1000 / source_fps)

print(f"Source: {source_fps:.2f} fps — keeping every {keep_every} frame(s) → {1000/frame_duration_ms:.1f} fps output ({frame_duration_ms} ms/frame)")

frames = []
frame_index = 0
while True:
    ret, frame = cap.read()
    if not ret:
        break

    if frame_index % keep_every == 0:
        # RGB for color data
        rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

        # HSV Value channel (0=black/transparent, 255=white/opaque) remapped to alpha
        hsv = cv2.cvtColor(frame, cv2.COLOR_BGR2HSV)
        v = hsv[:, :, 2].astype(np.float32) / 255.0
        v = np.clip(v * VALUE_SCALE, 0.0, 1.0)

        # Strong high-end compression: bright pixels become less opaque than linear mapping
        alpha_linear = np.power(v, ALPHA_GAMMA) * 255.0
        alpha = np.clip(alpha_linear * ALPHA_SCALE, 0, 255).astype(np.uint8)

        rgba = np.dstack((rgb, alpha))
        img = Image.fromarray(rgba, "RGBA").resize((450, 450), Image.Resampling.LANCZOS)
        frames.append(img)

    frame_index += 1

cap.release()

if not frames:
    print("ERROR: No frames were read from the video.")
else:
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
