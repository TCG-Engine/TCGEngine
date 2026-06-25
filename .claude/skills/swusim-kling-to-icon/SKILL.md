---
name: swusim-kling-to-icon
description: Use when turning a Kling-generated .mov (an effect/keyword animation on a pure-black background) into a SWUSim counter icon — a small 60x60 animated transparent .webp. Runs the mov->transparent-webp conversion then resizes to icon size. Input is one .mov path (usually under Assets/Icons/).
---

# SWUSim: Kling .mov → 60×60 Icon

Two-step pipeline that turns a Kling AI clip (an effect recorded on a **pure-black
background**) into a board-ready counter icon: transparent animated WebP, downscaled
to 60×60. Both DevTools already exist — this skill just orchestrates them.

- Step A — `DevTools/convert-mov-to-transparent-webp.py` (luminance→alpha; black → transparent)
- Step B — `DevTools/resize-icon.py` (fit to 60×60, preserve transparency + every frame)

Input: one `.mov` path (e.g. `Assets/Icons/exploit.mov`).
Output: `<base>.webp` at the same path/name, 60×60 animated transparent (overwrites
any existing `<base>.webp`).

## Step 0 — Deps (once per machine)

The scripts need `cv2`, `numpy`, `PIL`. If `python3 -c "import cv2,numpy,PIL"` fails:

```
python3 -m pip install --quiet opencv-python-headless pillow numpy
```

## Step 1 — Probe the source (confirm it's on black)

The transparency step assumes a **black background** (it derives alpha from
brightness). Verify before converting; set `IN` to the mov path:

```
IN=Assets/Icons/exploit.mov
python3 - <<PY
import cv2, numpy as np
cap=cv2.VideoCapture("$IN"); n=cap.get(cv2.CAP_PROP_FRAME_COUNT)
w=int(cap.get(cv2.CAP_PROP_FRAME_WIDTH)); h=int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
print(f"{w}x{h} fps={cap.get(cv2.CAP_PROP_FPS):.0f} frames={n:.0f}")
cap.set(cv2.CAP_PROP_POS_FRAMES,int(n//2)); ret,f=cap.read()
cb=np.mean([f[0:20,0:20].mean(),f[0:20,-20:].mean(),f[-20:,0:20].mean(),f[-20:,-20:].mean()]) if ret else -1
print(f"corner brightness={cb:.1f} (0=black; if not ~0, this isn't an on-black clip)")
PY
```

If corners aren't ~0, STOP and tell the user — the cutout won't be clean.

## Step 2 — Convert mov → transparent WebP

`convert-mov-to-transparent-webp.py` hardcodes its input/output paths, so run a temp
copy with the paths swapped (keeps the committed script untouched). It crops to content
and outputs 60×60 directly.

For a **bordered/ring icon** (a silver ring/frame with content or black inside it) also flip
on `PRESERVE_INTERIOR` — otherwise the per-pixel luminance alpha punches holes through the
interior black. With it on, only black connected to the frame border is removed; interior
black stays opaque. Leave it OFF for a pure glow effect (it would harden the soft falloff).
Most of these Kling keyword icons are ring-bordered, so default to on:

```
IN=Assets/Icons/exploit.mov
OUT="${IN%.mov}.webp"
python3 - <<PY
import re
src=open('DevTools/convert-mov-to-transparent-webp.py').read()
src=re.sub(r'^input_path = .*$',  'input_path = r"$PWD/$IN"',  src, flags=re.M)
src=re.sub(r'^output_path = .*$', 'output_path = r"$PWD/$OUT"', src, flags=re.M)
src=re.sub(r'^PRESERVE_INTERIOR = .*$', 'PRESERVE_INTERIOR = True', src, flags=re.M)  # ring icon; drop for a pure glow
open('/tmp/kling_conv.py','w').write(src)
PY
python3 /tmp/kling_conv.py
```

## Step 3 — Resize to the 60×60 icon (in place)

```
python3 DevTools/resize-icon.py "$OUT" "$OUT"
```

`resize-icon.py` preserves transparency and every animation frame, fits to a 60×60
box (centered, aspect-preserved), and writes WebP because the output ends in `.webp`.
Passing the same path overwrites the full-size intermediate, leaving only the icon.

## Step 4 — Verify

```
python3 - <<PY
from PIL import Image; import numpy as np, os
im=Image.open("$OUT"); im.seek(im.n_frames//2); a=np.array(im.convert("RGBA"))[:,:,3]
print(f"{os.path.basename('$OUT')}: {im.size} frames={im.n_frames} animated={im.is_animated} "
      f"transparent={(a==0).mean()*100:.0f}% size={os.path.getsize('$OUT')/1024:.0f}KB")
PY
```

Expect: `(60, 60)`, `animated=True`, a healthy transparent %, and a small (~100–150 KB)
file. Report the final path + size to the user.

## Notes

- **Wiring it up:** once you have `<base>.webp`, it's used as a schema counter, e.g.
  `Counters: <Field>=Image(Path=Assets/Icons/<base>.webp,Position=Bottom,ShowZero=false,Size=…)`
  in `Schemas/SWUSim/GameSchema.txt` (then regenerate). See the Coordinate counters
  (`coordinate_active.webp`) for the pattern. That wiring is a separate task — this
  skill only produces the asset.
- **Static PNG variant:** for a non-animated "inactive" icon exported on black, use
  `DevTools/remove-black-bg-png.py` (edge-connected flood fill — keeps interior black)
  then `resize-icon.py`.
- **Size knob:** pass a numeric arg to `resize-icon.py` for a non-60 box (e.g. `… 48`).
