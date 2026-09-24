---
name: png-to-webp
description: Convert a PNG (or other Pillow-readable image) to WebP — the enforced image format for SWUStats assets. Use when adding/replacing any raster asset (board backgrounds, icons, art) that is currently a .png/.jpg, or when asked to "make this a webp". Supports a quality param (1-100, default 70), optional resize, and producing the <base>.webp / <base>-mobile.webp board pair. Orchestrates DevTools/png-to-webp.py.
---

# PNG → WebP

WebP is the enforced raster format for SWUStats assets going forward. This skill wraps
the one-time tool `DevTools/png-to-webp.py` (built once; reused for every conversion).

Input: one or more image paths.
Output: `<base>.webp` beside each input (or `--output PATH` for a single input).

## Dependency (once per machine)

Needs Pillow with WebP support. Verify:

```
python3 -c "from PIL import Image, features; print('webp:', features.check('webp'))"
```

If it prints `webp: False` or Pillow is missing:

```
python3 -m pip install --quiet pillow
```

## Usage

Basic (quality 70 default), writes `foo.webp` next to `foo.png`:

```
python3 DevTools/png-to-webp.py path/to/foo.png
```

Common flags:

- `--quality N` — WebP quality **1-100, default 70**. Lower = smaller file.
- `--output PATH` — explicit output path (single input only).
- `--lossless` — lossless WebP (crisp line art / icons; larger).
- `--resize WxH` — fit within WxH, preserving aspect (e.g. `--resize 1200x2000`).
- `--delete-src` — remove the source file after a successful convert.

Batch several files at quality 80:

```
python3 DevTools/png-to-webp.py Assets/Icons/a.png Assets/Icons/b.png --quality 80
```

The tool prints the size before/after and the % saved, and exits non-zero if any file fails.

## Board backgrounds — the `<base>.webp` / `<base>-mobile.webp` pair

Board art follows a convention (see `SWUSim/Custom/GameLayoutDevice.php` →
`SWUBoardBackground()`): the desktop/tablet layout loads `<base>.webp` and the phone
layout loads `<base>-mobile.webp`. When adding or swapping a board, produce **both**
from the source PNG:

```
# desktop full-res
python3 DevTools/png-to-webp.py Assets/Boards/SWUSim/<name>.png --quality 70 \
    --output Assets/Boards/SWUSim/<name>.webp
# mobile portrait variant (downscaled)
python3 DevTools/png-to-webp.py Assets/Boards/SWUSim/<name>.png --quality 70 \
    --resize 1200x2000 --output Assets/Boards/SWUSim/<name>-mobile.webp
```

Then point `SWUBoardBackground()`'s `$base` at `./Assets/Boards/SWUSim/<name>`.

## Verify

Read the output `.webp` back (the Read tool renders it) to confirm it looks right and
transparency was preserved before deleting any source.
