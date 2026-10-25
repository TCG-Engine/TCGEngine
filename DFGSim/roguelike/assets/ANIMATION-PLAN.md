# Enemy animation production

The original workflow survives in `DFGSim/assets/README.md`, `kling-idle-v2-prompt.txt`, `kling-knockout-prompt.txt`, `DevTools/convert-fighter-idle.py`, and `DevTools/build-dfg-reactions.py`. The original Kling project is also accessible.

The matching enemy set is **24 generated clips**: idle, block, light hit (1–2 damage), medium hit (3–4), heavy hit (5+), and knockout for each of fox, tortoise, crane tengu, and dragon. The players do not currently have a separate attack animation.

`animation-jobs.json` records the exact prompt, reference, first/last frame, source filename, settings, conversion speed, and estimated cost for every job. The full batch has now been generated and downloaded for 792 credits. See `animations/ledger.json` for charges, result links, exact source URLs, and verification, and `animations/README.md` for conversion details.

Use Kling 4.0 Keyframes, 720p, adaptive ratio, one output. Reactions are three seconds; idle and knockout are five. Use the same black-backed character image as first and last frame for idle and reactions. Knockout uses a first frame only and ends lying down permanently. Preserve the existing sprites' cartoon style, facing, framing, and proportions.

The four black-backed sibling inputs have been prepared with built-in imagegen and inspected at `animations/inputs/<enemy>-black.png`, preserving the source sprites and providing enough canvas margin for recoil and the horizontal knockout pose. Their generation prompts are saved in `animations/inputs/PROMPTS.json`. Do not use the retired painterly atlas.

At the recorded and currently displayed 720p rate (27 credits for three seconds; 45 for five), each enemy costs 198 credits and the complete set costs **792 credits**, excluding retries. Verify the live cost before each paid submission. The user has approved the full 792-credit cap. The user also explicitly approved uploading the sprites and black-backed inputs to Kling.ai. The full batch was submitted for exactly 792 credits; no paid retries were made. Existing credits are consumed even without a new card payment.

The source videos are saved to `animations/sources/<enemy>-<kind>.mp4`. Convert using the exact player transparency pipeline:

```sh
python DevTools/build-dfg-enemy-animations.py --enemy fox --force
```

The converter samples 12 frames per source second at 480 pixels high, removes only edge-connected near-black, preserves enclosed dark details, and feathers edges. Idle and knockout play at 1.5×; reactions at 2.5×. Idle loops forever; reactions and knockout play once. A lossless final knockout still holds the defeated pose. The builder refuses an incomplete requested source set and writes a manifest with measured duration and versioned URLs.

The converted manifest is connected to the shared `fighter-reactions.js` lifecycle. All 24 jobs are verified and complete; see `animations/README.md` for validation and reproduction commands.
