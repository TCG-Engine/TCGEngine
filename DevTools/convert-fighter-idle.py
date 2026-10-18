"""Convert black-backed Kling videos/stills to transparent, looping WebP.

Requires: pip install opencv-python numpy pillow
Adapted from convert-mov-to-transparent-webp.py and remove-black-bg-png.py.
Example:
  python DevTools/convert-fighter-idle.py DFGSim/assets/fighter-blue-idle.mp4 --height 640 --ping-pong
"""

import argparse
from pathlib import Path

import cv2
import numpy as np
from PIL import Image


def remove_background(image, threshold=12, feather=8):
    """Key edge-connected near-black while preserving enclosed dark details."""
    rgba = np.array(image.convert("RGBA"))
    value = rgba[:, :, :3].max(axis=2).astype(np.float32)
    candidate = (value <= threshold + feather).astype(np.uint8)
    _, labels = cv2.connectedComponents(candidate, connectivity=8)
    border = np.unique(np.concatenate((labels[0], labels[-1], labels[:, 0], labels[:, -1])))
    border = border[border != 0]
    background = np.isin(labels, border)
    if feather:
        alpha = np.clip((value - threshold) / feather, 0, 1) * 255
    else:
        alpha = np.where(value > threshold, 255, 0)
    rgba[:, :, 3] = np.minimum(rgba[:, :, 3], np.where(background, alpha, 255)).astype(np.uint8)
    # Avoid colored fringes in viewers that inspect transparent RGB values.
    rgba[rgba[:, :, 3] == 0, :3] = 0
    return Image.fromarray(rgba)


def read_frames(path, fps):
    if path.suffix.lower() in {".png", ".jpg", ".jpeg", ".bmp"}:
        with Image.open(path) as image:
            return [image.convert("RGBA")], [1000]
    cap = cv2.VideoCapture(str(path))
    if not cap.isOpened():
        raise ValueError(f"Cannot open video: {path}")
    source_fps = cap.get(cv2.CAP_PROP_FPS)
    if not np.isfinite(source_fps) or source_fps <= 0:
        cap.release()
        raise ValueError("Video has no valid frame rate")
    frames, times = [], []
    index, next_time = 0, 0.0
    try:
        while True:
            ok, frame = cap.read()
            if not ok:
                break
            time = index / source_fps
            if time + 1e-8 >= next_time:
                frames.append(Image.fromarray(cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)))
                times.append(round(time * 1000))
                next_time += 1 / min(fps, source_fps)
            index += 1
    finally:
        cap.release()
    if not frames:
        raise ValueError("No frames decoded")
    # Source timestamps preserve playback speed even for non-integer FPS ratios.
    end = round(index / source_fps * 1000)
    durations = [max(1, b - a) for a, b in zip(times, times[1:] + [end])]
    return frames, durations


def convert(source, destination, *, fps=12, height=480, threshold=12, feather=8,
            ping_pong=False, force=False, quality=90, lossless=False, speed=1.0, loop_count=0):
    source, destination = Path(source), Path(destination)
    if source.resolve() == destination.resolve():
        raise ValueError("Input and output must be different files")
    if destination.suffix.lower() != ".webp":
        raise ValueError("Output must have a .webp extension")
    if destination.exists() and not force:
        raise FileExistsError(f"Output exists (use --force to replace): {destination}")
    if not source.is_file():
        raise ValueError(f"Input does not exist: {source}")
    if not 1 <= fps <= 60 or height < 1:
        raise ValueError("FPS must be 1..60 and height must be positive")
    if not 0 <= threshold <= 255 or not 0 <= feather <= 255 - threshold:
        raise ValueError("Threshold and feather must be nonnegative and sum to <= 255")
    if not 0 <= quality <= 100:
        raise ValueError("Quality must be 0..100")
    if not np.isfinite(speed) or speed <= 0:
        raise ValueError("Speed must be a finite positive multiplier")
    if not isinstance(loop_count, int) or not 0 <= loop_count <= 65535:
        raise ValueError("Loop count must be an integer from 0 to 65535")
    raw, durations = read_frames(source, fps)
    frames = []
    size = None
    for image in raw:
        frame = remove_background(image, threshold, feather)
        if size is None:
            scale = min(1, height / frame.height)
            size = (max(1, round(frame.width * scale)), max(1, round(frame.height * scale)))
        if frame.size != size:
            frame = frame.resize(size, Image.Resampling.LANCZOS)
        frames.append(frame)
    del raw
    if not any(frame.getchannel("A").getextrema()[0] == 0 for frame in frames):
        raise ValueError("No transparent background detected; inspect source/threshold before exporting")
    if not any(frame.getchannel("A").getextrema()[1] > 0 for frame in frames):
        raise ValueError("All frames are transparent; inspect source/threshold")
    # Forward then reverse without duplicating endpoints: no jump at loop boundary.
    if ping_pong and len(frames) > 2:
        frames += frames[-2:0:-1]
        durations += durations[-2:0:-1]
    # Scale cumulative timestamps to avoid accumulating per-frame rounding errors.
    times = [0] + [round(int(time) / speed) for time in np.cumsum(durations)]
    durations = [b - a for a, b in zip(times, times[1:])]
    if min(durations) < 10:
        raise ValueError("Speed is too high: frame durations below 10 ms are unreliable in browsers")
    destination.parent.mkdir(parents=True, exist_ok=True)
    frames[0].save(destination, format="WEBP", save_all=True, append_images=frames[1:],
                   duration=durations, loop=loop_count, lossless=lossless, quality=quality, method=4)
    # Verify the encoded artifact, including timing and alpha, not just in-memory frames.
    with Image.open(destination) as result:
        encoded_duration = 0
        for index in range(result.n_frames):
            result.seek(index)
            result.load()
            encoded_duration += result.info.get("duration", 0)
            alpha = result.convert("RGBA").getchannel("A")
            if alpha.getextrema()[0] != 0:
                raise ValueError(f"Encoded frame {index} lost background transparency")
        if len(frames) > 1 and encoded_duration != sum(durations):
            raise ValueError("Encoded animation timing does not match requested playback timing")
        print(f"{destination}: {result.width}x{result.height}, {result.n_frames} frames, "
              f"{encoded_duration} ms, transparent alpha, {destination.stat().st_size:,} bytes")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("input", type=Path)
    parser.add_argument("output", nargs="?", type=Path, help="Default: <input>-transparent.webp")
    parser.add_argument("--fps", type=float, default=12)
    parser.add_argument("--speed", type=float, default=1.0, help="Playback multiplier: 1.5 plays all sampled frames 50%% faster")
    parser.add_argument("--loop-count", type=int, default=0, help="0 loops forever (idle); 1 plays once (reaction)")
    parser.add_argument("--height", type=int, default=480, help="Maximum height; aspect ratio is preserved")
    parser.add_argument("--quality", type=int, default=90, help="WebP color quality; alpha is encoded losslessly")
    parser.add_argument("--lossless", action="store_true", help="Use lossless color encoding (larger files)")
    parser.add_argument("--threshold", type=int, default=12)
    parser.add_argument("--feather", type=int, default=8)
    parser.add_argument("--ping-pong", action="store_true", help="Append reversed motion for a continuous loop (nearly doubles duration)")
    parser.add_argument("--force", action="store_true")
    args = parser.parse_args()
    try:
        convert(args.input, args.output or args.input.with_name(args.input.stem + "-transparent.webp"),
                fps=args.fps, height=args.height, threshold=args.threshold, feather=args.feather,
                ping_pong=args.ping_pong, force=args.force, quality=args.quality, lossless=args.lossless,
                speed=args.speed, loop_count=args.loop_count)
    except (ValueError, OSError) as error:
        parser.exit(1, f"Error: {error}\n")


if __name__ == "__main__":
    main()
