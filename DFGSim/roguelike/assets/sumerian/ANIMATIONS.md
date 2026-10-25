# Sumerian enemy animations

Imdugud, Huwawa, and the Bull of Heaven each have six states matching the existing player characters: idle, block, light hit (1–2 damage), medium hit (3–4), heavy hit (5+), and knockout. There is no separate attack clip in the original player set.

The user explicitly approved uploading these sprites and their black-backed inputs to Kling.ai and spending up to **594 existing credits**. All 18 jobs were submitted for exactly 594 credits, with no paid retries. `animation-ledger.json` records each submission, charge, result page, exact source URL, download, and validation. This is separate from the previous four-enemy 792-credit batch.

## Assets and reproduction

- Original base sprites and their exact prompts: this directory and `PROMPTS.json`.
- Black-backed animation inputs: `../animations/inputs/{imdugud,huwawa,bull-of-heaven}-black.png`. Built-in imagegen input-preparation prompts: `ANIMATION-INPUT-PROMPTS.json`.
- Exact 18 video prompts and settings: `animation-jobs.json`.
- Untouched generated MP4s: `../animations/sources/<enemy>-<state>.mp4`.
- Transparent WebPs, idle stills, and held knockout stills: `../animations/`.
- Runtime manifest: `../animations/enemy-animations.json`, exposed by the existing run API.
- Replay gallery: `DFGSim/roguelike/sumerian-animations.php`. The encounter roster and movesets are not changed by this art batch.

Kling settings match the preceding batch: Kling 4.0 Keyframes, 720p, adaptive aspect ratio, one output. Idle and knockout are five seconds (45 credits); the four reactions are three seconds (27 credits each). Idle/reactions use the identical supplied first and last guard frames; knockout has only a first frame and ends lying down.

Conversion uses the existing player transparency pipeline: edge-connected black removal at threshold 4/feather 4, 480-pixel height, 12 sampled source frames per second, 1.5× idle/knockout playback and 2.5× reactions. Idle loops; other clips play once. Knockout has a lossless final still. Total encoded duration remains 3,361 ms for idle/knockout and 1,217 ms for reactions.

Some generated clips contained brief unwanted attacker fists, streaks, or impact effects despite the prompt. `conversion.omitSampledFrames` records the exact original sampled frames excluded during local conversion. Remaining cumulative timestamps are rescaled to preserve total playback duration. The original MP4s are retained; cleanup costs no generation credits. Contact sheets under `../animations/qa/` document inspection; sheets labeled `delivered` are refreshed after cleanup, while other inspection sheets may show discarded samples.

```powershell
python -u DevTools/build-dfg-enemy-animations.py --jobs DFGSim/roguelike/assets/sumerian/animation-jobs.json --force
python DevTools/verify-dfg-enemy-animations.py --jobs DFGSim/roguelike/assets/sumerian/animation-jobs.json
$env:ROGUE_ANIMATION_KEYS='imdugud,huwawa,bull-of-heaven'
node DFGSim/roguelike/verify-animations.mjs
```

The browser verifier uses fixture movesets to exercise the actual shared fight renderer without adding these art-only enemies to runs. It covers both desktop and mobile in Chromium and WebKit, including reduced motion, hit tiers, return to idle, held knockout, reset, and ordinary rerenders during reactions.

Validation passed: 18 distinct source videos, all 749 encoded transparent frames, matching held poses, measured timing and loop modes, and 12 Chromium/WebKit desktop/mobile cases through the shared fight renderer. The original four-enemy asset set remains unchanged.

The gallery also passed four Chromium/WebKit desktop/mobile cases: all 18 clips loaded and replayed, with no horizontal overflow or script errors.
