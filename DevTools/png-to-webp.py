#!/usr/bin/env python3
"""Convert PNG (or any Pillow-readable image) to WebP.

Webp is the enforced image format for SWUStats assets going forward. This is the
"make it once" tool the png-to-webp skill orchestrates.

Usage:
    python3 DevTools/png-to-webp.py <input...> [--quality N] [--output PATH]
                                    [--lossless] [--resize WxH] [--delete-src]

  <input...>      One or more image paths. Globs are fine if your shell expands them.
  --quality N     WebP quality 1-100 (default 70). Ignored when --lossless.
  --output PATH   Output path (single input only). Default: same name, .webp ext.
  --lossless      Lossless WebP (larger; for line art / icons that must stay crisp).
  --resize WxH    Resize to fit within WxH (preserves aspect ratio). e.g. 1080x1920.
  --delete-src    Remove the source file after a successful conversion.

Alpha is preserved. Exit code is non-zero if any file fails.
"""
import argparse
import os
import sys

try:
    from PIL import Image
except ImportError:
    sys.exit("Pillow is required: python3 -m pip install --quiet pillow")


def _human(n):
    size = float(n)
    for unit in ("B", "KB", "MB", "GB"):
        if size < 1024 or unit == "GB":
            return f"{size:.0f}{unit}" if unit == "B" else f"{size:.1f}{unit}"
        size /= 1024


def convert(inp, out, quality, lossless, resize):
    img = Image.open(inp)
    # Palette / LA images → RGBA so WebP keeps transparency correctly.
    if img.mode in ("P", "LA"):
        img = img.convert("RGBA")
    if resize:
        img.thumbnail(resize, Image.LANCZOS)  # fit-within, preserves aspect
    save_kwargs = {"method": 6}
    if lossless:
        save_kwargs["lossless"] = True
    else:
        save_kwargs["quality"] = quality
    img.save(out, "WEBP", **save_kwargs)
    return out


def parse_resize(val):
    try:
        w, h = val.lower().split("x")
        return (int(w), int(h))
    except Exception:
        raise argparse.ArgumentTypeError("--resize must look like WIDTHxHEIGHT, e.g. 1080x1920")


def main(argv=None):
    ap = argparse.ArgumentParser(description="Convert images to WebP.")
    ap.add_argument("inputs", nargs="+")
    ap.add_argument("--quality", type=int, default=70)
    ap.add_argument("--output")
    ap.add_argument("--lossless", action="store_true")
    ap.add_argument("--resize", type=parse_resize)
    ap.add_argument("--delete-src", action="store_true")
    args = ap.parse_args(argv)

    if not (1 <= args.quality <= 100):
        ap.error("--quality must be between 1 and 100")
    if args.output and len(args.inputs) > 1:
        ap.error("--output can only be used with a single input")

    failures = 0
    for inp in args.inputs:
        if not os.path.isfile(inp):
            print(f"  SKIP (not found): {inp}")
            failures += 1
            continue
        out = args.output or (os.path.splitext(inp)[0] + ".webp")
        try:
            src_sz = os.path.getsize(inp)
            convert(inp, out, args.quality, args.lossless, args.resize)
            out_sz = os.path.getsize(out)
            pct = (1 - out_sz / src_sz) * 100 if src_sz else 0
            mode = "lossless" if args.lossless else f"q{args.quality}"
            print(f"  {inp} -> {out}  ({_human(src_sz)} -> {_human(out_sz)}, -{pct:.0f}%, {mode})")
            if args.delete_src and os.path.abspath(inp) != os.path.abspath(out):
                os.remove(inp)
                print(f"    removed source {inp}")
        except Exception as e:
            print(f"  FAIL {inp}: {e}")
            failures += 1

    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main())
