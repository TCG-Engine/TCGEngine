# Enemy animation assets

Four complete Kling 4.0 sets: `fox`, `tortoise`, `tengu`, and `dragon`. Each has idle, block, light hit (1–2 damage), medium hit (3–4), heavy hit (5+), and knockout. The original player animations and fight interface remain in use.

The user approved uploading the four sprites and their black-backed versions to Kling.ai, and approved a maximum of 792 credits. All 24 jobs were submitted for **792 credits total**, with no paid retries. `ledger.json` records each charge, result-page URL, exact source-video URL, download, and verification state. The original generated videos are preserved under `sources/`.

## Rebuild

```sh
python -u DevTools/build-dfg-enemy-animations.py --force
python DevTools/verify-dfg-enemy-animations.py
node DFGSim/roguelike/verify-animations.mjs
```

The builder imports `DevTools/convert-fighter-idle.py`, the same edge-connected black-removal and animated-WebP pipeline used by the players. Sources were generated at 720p in Keyframes mode with one output and adaptive aspect ratio. Idle and knockout sources are five seconds; reactions are three. Idle/reactions use identical supplied first and last frames. Knockout uses only a first frame and ends lying down.

- Sampling: 12 source frames per second, aspect ratio preserved at 480 pixels high.
- Playback: 1.5× for idle/knockout (3,361 ms); 2.5× for reactions (1,217 ms).
- Loop mode: idle repeats; reactions and knockout play once. Knockout has a lossless `-final.webp` held after defeat, including on restored matches and reduced motion.
- Alpha: threshold 4, feather 4. These inputs have darker outlines than the old player inputs; the original threshold 12/feather 8 thinned those outlines. `qa/matte-comparison.png` records the adjustment.
- Dragon light-hit cleanup: omit source-sampled frames 8–12, which contained a brief unwanted generated fist, then rescale the remaining cumulative timestamps to preserve the 1,217 ms reaction. The untouched source MP4 remains available. This is a local edit, not another generation.

`enemy-animations.json` contains measured clip durations, versioned URLs, static fallbacks, and held knockout frames. The run API exposes it. A set activates only when all six clips and its final still are present. `fighter-reactions.js` owns playback for both player and enemy: independent seats, tier selection, interruption, idle restoration, held knockout, reduced motion, loading fallback, and no restart on ordinary state updates.

`qa/*-contact.png` shows the source motion for all six states of each creature. The source dragon sheet includes the discarded fist frames; inspect the delivered `dragon-light.webp` for the cleaned sequence. Black-backed input prompts are saved under `inputs/PROMPTS.json`; the exact video prompts and settings are in the parent `animation-jobs.json`.

Current clips use a wider canvas than the original fighters to leave room for knockout poses. The run-specific arena styles compensate for that transparent padding while retaining the shared arena layout.

Verification passed: 24 distinct source videos, all 1,074 encoded frames checked for transparency, measured timing and loop modes, matching held poses, and 16 Chromium/WebKit desktop/mobile playback cases. The existing four-case roguelike browser flow also passes. Firefox could not launch in this local runtime (`spawn UNKNOWN`).

## Sumerian additions

Three additional six-state sets (Imdugud, Huwawa, Bull of Heaven) are saved beside these assets and included in the runtime manifest. Their separate 594-credit production ledger, exact cleanup frame lists, prompts, and build commands are documented in `../sumerian/ANIMATIONS.md`. Preview them at `DFGSim/roguelike/sumerian-animations.php`; they are art-only additions awaiting encounter design.
