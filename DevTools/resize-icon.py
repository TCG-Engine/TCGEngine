# Prereq:
# pip install pillow
#
# Usage:
#   python resize-icon.py <input.(png|webp)> [output] [size]
#
# Resizes an image DOWN to an icon (default 60x60), keeping transparency. Aspect
# ratio is preserved: the art is fit inside the box and centered on a transparent
# canvas, so non-square sources aren't distorted (square sources fill the box).
#
# Works on static PNG and static/ANIMATED WebP — every frame of an animated WebP is
# resized and the frame durations + looping are preserved.
#
# Output format follows the OUTPUT extension when given; otherwise it writes
# "<input>-<size>.<ext>" next to the input using the input's own format (so an
# animated .webp stays an animated .webp, a .png stays a .png). The original is
# never overwritten unless you pass it explicitly as the output path.
#
# Companion to convert-mov-to-transparent-webp.py / remove-black-bg-png.py.

import os
import sys

from PIL import Image

SIZE = 60  # default icon edge length (square box); override as a numeric CLI arg


def fit_icon(frame: Image.Image, size: int) -> Image.Image:
    """Fit an RGBA frame inside a size x size box, centered on a transparent canvas."""
    frame = frame.convert("RGBA")
    w, h = frame.size
    scale = min(size / w, size / h)
    new_w, new_h = max(1, round(w * scale)), max(1, round(h * scale))
    resized = frame.resize((new_w, new_h), Image.Resampling.LANCZOS)
    canvas = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    canvas.paste(resized, ((size - new_w) // 2, (size - new_h) // 2), resized)
    return canvas


def save_kwargs_for(ext: str) -> dict:
    if ext == ".webp":
        return dict(format="WEBP", quality=90, method=6)
    return dict(format="PNG", optimize=True)


def main() -> None:
    if len(sys.argv) < 2:
        print("Usage: python resize-icon.py <input.(png|webp)> [output] [size]")
        sys.exit(1)

    input_path = sys.argv[1]
    in_base, in_ext = os.path.splitext(input_path)

    # Remaining args: a numeric one is the size, a non-numeric one is the output path.
    size = SIZE
    output_path = None
    for arg in sys.argv[2:]:
        if arg.isdigit():
            size = int(arg)
        else:
            output_path = arg
    if output_path is None:
        output_path = f"{in_base}-{size}{in_ext}"

    out_ext = os.path.splitext(output_path)[1].lower()
    save_kwargs = save_kwargs_for(out_ext)

    im = Image.open(input_path)
    animated = getattr(im, "is_animated", False) and getattr(im, "n_frames", 1) > 1

    if animated:
        frames, durations = [], []
        for i in range(im.n_frames):
            im.seek(i)
            frames.append(fit_icon(im, size))
            durations.append(im.info.get("duration", 100))
        frames[0].save(
            output_path,
            save_all=True,
            append_images=frames[1:],
            duration=durations,
            loop=im.info.get("loop", 0),
            **save_kwargs,
        )
        detail = f"{len(frames)} frames"
    else:
        fit_icon(im, size).save(output_path, **save_kwargs)
        detail = "static"

    src_kb = os.path.getsize(input_path) / 1024.0
    out_kb = os.path.getsize(output_path) / 1024.0
    print(
        f"Wrote {output_path}  ({size}x{size}, {detail})  "
        f"{src_kb:.0f} KB -> {out_kb:.0f} KB"
    )


if __name__ == "__main__":
    main()
