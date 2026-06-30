# Prereq:
# pip install pillow numpy opencv-python
#
# Usage:
#   python remove-black-bg-png.py <input.png> [output.png]
#
# Removes the BLACK BACKGROUND from a still image (PNG or any RGB image) without
# touching black that belongs to the icon. It does this by flood-filling the black
# region inward from the image border: only black pixels connected to the edge are
# treated as background. Black enclosed by the icon (outlines, dark detail) is kept.
# Near-black edge pixels within FEATHER ramp to partial alpha for an anti-aliased
# cutout. If output is omitted, writes "<input>-transparent.png" next to the input
# (the original is never overwritten unless you pass it as the output path).
#
# Static counterpart to convert-mov-to-transparent-webp.py (same luminance->alpha
# trick, for an image instead of a video). Use it for icons exported on black, e.g.
# coordinate_inactive.png.

import os
import sys

import cv2
import numpy as np
from PIL import Image

# --- Tunables ---
BLACK_THRESHOLD = 16   # max(R,G,B) <= this is "pure-black background" -> fully transparent
FEATHER = 16           # width (in 0-255 brightness) of the transparent->opaque ramp just above the
                       # threshold; gives anti-aliased edges. Set 0 for a hard cutoff (no soft edge).


def remove_black_bg(img: Image.Image) -> Image.Image:
    """Return an RGBA copy of img with only its EDGE-CONNECTED black background
    mapped to transparency. Interior black (part of the icon) is preserved."""
    rgba = np.array(img.convert("RGBA"))
    rgb = rgba[:, :, :3].astype(np.float32)
    src_alpha = rgba[:, :, 3].astype(np.float32)

    # "Blackness" is the brightest channel, so a pixel only counts as background when
    # EVERY channel is dark — colored-but-dim pixels are kept.
    value = rgb.max(axis=2)

    # Candidate background = anything dark enough to possibly be background (includes
    # the feathered edge band so the cutout can reach the icon's solid edge).
    candidate = (value <= (BLACK_THRESHOLD + FEATHER)).astype(np.uint8)

    # Label connected dark regions; the background is whichever region(s) touch the
    # border. Interior black is enclosed by the (bright) icon, so it never connects.
    num, labels = cv2.connectedComponents(candidate, connectivity=8)
    border_labels = set(labels[0, :]) | set(labels[-1, :]) | set(labels[:, 0]) | set(labels[:, -1])
    border_labels.discard(0)  # 0 = the non-dark region (the icon itself)
    bg_mask = np.isin(labels, list(border_labels))

    # Background pixels fade by brightness (pure black -> 0, up the feather ramp ->
    # opaque); everything else (the icon, incl. its interior black) stays opaque.
    if FEATHER > 0:
        ramp = np.clip((value - BLACK_THRESHOLD) / FEATHER, 0.0, 1.0) * 255.0
    else:
        ramp = np.where(value > BLACK_THRESHOLD, 255.0, 0.0)
    alpha = np.where(bg_mask, ramp, 255.0)

    # Respect any transparency already present in the source.
    out_alpha = np.minimum(src_alpha, alpha).astype(np.uint8)
    rgba[:, :, 3] = out_alpha
    return Image.fromarray(rgba, "RGBA")


def main() -> None:
    if len(sys.argv) < 2:
        print("Usage: python remove-black-bg-png.py <input.png> [output.png]")
        sys.exit(1)

    input_path = sys.argv[1]
    if len(sys.argv) >= 3:
        output_path = sys.argv[2]
    else:
        base, _ = os.path.splitext(input_path)
        output_path = base + "-transparent.png"

    img = Image.open(input_path)
    out = remove_black_bg(img)
    transparent_pct = (np.array(out)[:, :, 3] == 0).mean() * 100.0
    out.save(output_path, format="PNG")
    print(
        f"Wrote {output_path}  ({out.size[0]}x{out.size[1]}, "
        f"{transparent_pct:.0f}% transparent)"
    )


if __name__ == "__main__":
    main()
