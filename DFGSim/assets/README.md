# Arena art

The active arena uses an independent courtyard, the second blue Kling idle loop, and the first red Kling idle loop, both at 1.5x speed:

- `arena-courtyard-ambient.mp4`: lightly animated 30-second courtyard loop.
- `arena-courtyard-kling.png`: matching still for loading, playback failure, and reduced motion. `arena-courtyard.png` preserves the original background-only artwork.
- `fighter-blue-idle-v2.webp`: blue player fighter, facing right, at 1.5x playback speed. Reduced motion selects `fighter-blue-transparent.webp` instead.
- `fighter-red-idle.webp`: red opponent/bot fighter, facing left, at 1.5x playback speed. Reduced motion selects `fighter-red-transparent.webp` instead.

The courtyard and source `fighter-blue-black.png` / `fighter-red-black.png` images were generated with built-in imagegen. The black-backed PNGs are retained as animation inputs.

These preserve the rough prototype style: flat colors, imperfect outlines, and faceless sparring figures. They are AI-generated placeholder art. Blue and red identify seats and remain fixed when players change hand colors. Health, round, commitments, and controls are live HTML.

The stage and two `.arena-fighter` images live inside `.arena-art`, so existing stage impact effects move all three together. Both use Kling animations without extra CSS breathing. Reduced-motion settings select transparent stills for both fighters. The first blue loop is retained as an unused alternative. No attack playback or character selection is implemented yet.

The earlier composite `prototype-arena.png` and polished `temple-arena.png` are retained as unused source references, including on mobile.

## Ambient courtyard

The background was generated in Kling 4.0 Keyframes mode at 720p, 30 seconds, adaptive ratio, one output, with `arena-courtyard-kling.png` as identical first/last frames. The original panorama exceeded Kling's 5:2 aspect-ratio limit, so built-in imagegen created the compatible sibling with more sky/floor. The complete preparation and animation prompts are in `kling-courtyard-prompt.txt`. Approved cost: 270 credits. Result: `https://kling.ai/app/creations/grid?project=929951353903251461&slotId=940788147658162209`.

`arena-courtyard-ambient-source.mp4` preserves the downloaded source. `arena-courtyard-ambient.mp4` is the silent web-ready export: 1468x620, 24 fps, 30.042 seconds, H.264/yuv420p, 770,141 bytes. Playback keeps the original timing and loops independently behind the fighters. The motion is limited to gentle banners, clouds, and lake ripples with stable architecture and camera framing.

`arena-background.js` loads the video when a match is shown, pauses it on the main menu or while the document is hidden, and uses the matching still for reduced motion or playback failure. No video is fetched while reduced motion is initially enabled. The video remains inside `.arena-art`, so existing hit shake effects still work; fighters and the shading overlay stay above it. PHP omits the video element when the generated file is absent.

```powershell
python DevTools/prepare-dfg-background.py DFGSim/assets/arena-courtyard-ambient-source.mp4 --force
node DevTools/ui-harness/dfg-background.mjs
```

The preparation script uses FFmpeg/ffprobe, preserves timing and aspect ratio, removes audio, encodes H.264 with fast-start metadata, verifies the output, and atomically replaces the game asset. The source is never overwritten.

## Combat reactions

Both fighters have four independent Kling reactions, selected by final damage received:

| Damage | Clip kind | Motion |
| --- | --- | --- |
| 0 | block | Compact crossed forearm guard, brace, recover |
| 1-2 | light | Small flinch and recoil, recover |
| 3-4 | medium | Clear torso recoil and knee compression, recover |
| 5+ | heavy | Larger recoil and balance recovery, remain standing |

Sources are `fighter-<blue|red>-hit-<light|medium|heavy|block>.mp4`; their transparent single-play counterparts use `.webp`. All eight were generated with Kling 4.0 Keyframes at 720p, three seconds, adaptive ratio, one output, using the corresponding black-backed PNG as identical first/last frames. Approved cost: 27 credits each, 216 total. Blue faces right and recoils left; red faces left and recoils right. The prompts request one reaction and recovery, a locked camera, full-body framing, no attacker, attacks, falling, scenery, effects, or text, preserving the original drawing and colors. Light/medium/heavy prompts increase recoil; block explicitly requests forearm defense with only a small impact jolt.

`DevTools/build-dfg-reactions.py` rebuilds all ten transparent clips (eight reactions and two knockouts) and `fighter-reactions.json` with their actual encoded playback duration and versioned URLs:

```powershell
python DevTools/build-dfg-reactions.py --force
```

The builder samples 12 frames per source second, preserves aspect ratio at 480px high, and plays at 2.5x speed: about 1.217 seconds per reaction at approximately 30 fps. It uses `--loop-count 1` so each clip plays once. Idle loops retain infinite looping. The manifest supplies exact restore timing; the client restores the idle after playback, restarts repeated reactions, and handles both seats independently. Missing assets and image-load errors leave/restore idle. Changing the reduced-motion preference or starting another match cancels pending playback. Reduced motion uses the existing stills.

The combat hook uses the opponent's final damage stat on resolution, rather than net life loss, so healing cannot hide a hit and overkill retains its true tier. Zero-damage blocks trigger only when combat resolves; ordinary polls and selection changes do not replay them. Existing damage numbers, health meters, and stage impacts continue independently.

```powershell
node DevTools/ui-harness/dfg-fighter-reactions.mjs
```

## Knockouts

Both fighters have a separate knockout: `fighter-<blue|red>-hit-knockout.mp4`, a transparent single-play `.webp`, and a `-final.webp` defeated still. Kling generated each at 720p, five seconds, with only the corresponding black-backed first frame and no end frame. Each performs one fall and stays down. Approved and spent cost: 45 credits each, 90 total. Exact settings and prompts are in `kling-knockout-prompt.txt`.

The builder samples 61 frames at 480px high and plays at 1.5x speed (3.361 seconds), then extracts the final frame for persistent display. Rebuild only these assets with:

```powershell
python DevTools/build-dfg-reactions.py --kind knockout --force
node DevTools/ui-harness/dfg-knockouts.mjs
```

The authoritative finished-match winner triggers the losing fighter's knockout; draws knock down both. Knockout overrides ordinary damage reactions and holds the final pose until a new match resets the fighters. Repeated state updates cannot restart the fall or return a defeated fighter to idle. Reduced motion and restored finished matches show the defeated still immediately. The manifest records `finalSrc` as well as clip duration.

## Kling idle animation

### Second blue animation (active)

`fighter-blue-idle-v2.mp4` is a second Kling generation using the original blue PNG as identical first/last frames, 720p, five seconds, adaptive ratio. It cost an additional approved 45 credits. The exact revised prompt is saved in `kling-idle-v2-prompt.txt`: knee-driven bobbing and quiet breathing, with the guard fixed relative to the torso and no independent arm motion. Kling result: `https://kling.ai/app/creations/grid?project=929951353903251461&slotId=940625583803875356`.

`fighter-blue-idle-v2.webp` is the approved transparent game asset: 458x480, retaining all 61 frames at 1.5x playback speed (3.361 seconds per loop), about 1.4 MB. All encoded frames passed alpha and timing validation. The clip has visible body rise/settle. The game versions its URL by file modification time so speed changes load on refresh. No second red generation has been purchased.

```powershell
python DevTools/convert-fighter-idle.py DFGSim/assets/fighter-blue-idle-v2.mp4 DFGSim/assets/fighter-blue-idle-v2.webp --speed 1.5 --force
```

### First generation

Both animations were generated in Kling 4.0 Keyframes mode at 720p, five seconds, one output per fighter, and adaptive aspect ratio. Each used the identical black-backed source image as both first and last frames, with a prompt requesting subtle breathing. The approved cost was 45 credits each (90 total).

`fighter-blue-idle.mp4` and `fighter-red-idle.mp4` preserve the downloaded black-backed originals: 920x964, 24 fps, 121 frames, approximately 5.042 seconds. Their transparent `fighter-*-idle.webp` versions are 458x480, retaining all 61 sampled frames and playing at 1.5x speed (3.361 seconds per loop, approximately 18 fps). Each is about 1.4 MB. The first red animation is active; the first blue animation is unused.

Suggested prompt (use for each fighter):

> A seamless looping fighting-game idle animation of this single martial artist. Keep the exact character design, outfit color, blank faceless head, rough dark outlines, proportions, and facing direction. Feet stay planted in the same wide guarded stance. Very subtle breathing, a gentle weight shift, and tiny natural fist and shoulder movements. Keep the entire body and both feet within frame. Locked camera, no zoom, no pan, no cuts. Preserve a perfectly solid pure black background throughout. No attacks, steps, turns, facial features, extra limbs, additional characters, scenery, particles, lighting changes, or text. End in the starting pose for a smooth loop.

### Convert black to transparency

`DevTools/convert-fighter-idle.py` adapts the existing GrandArchiveSim-era icon tools (`convert-mov-to-transparent-webp.py` and `remove-black-bg-png.py`) into a command-line converter for fighter footage. Install `opencv-python`, `numpy`, and `pillow` in your Python environment. From the repository root:

```powershell
python DevTools/convert-fighter-idle.py DFGSim/assets/fighter-blue-idle.mp4 DFGSim/assets/fighter-blue-idle.webp --height 480 --fps 12 --speed 1.5
python DevTools/convert-fighter-idle.py DFGSim/assets/fighter-red-idle.mp4 DFGSim/assets/fighter-red-idle.webp --height 480 --fps 12 --speed 1.5
```

The converter removes edge-connected near-black, preserves enclosed dark details and existing alpha, feathers the boundary, keeps aspect ratio and source timing, and exports animated WebP with infinite looping. Color uses quality 90, with lossless alpha; `--lossless` enables lossless color too. It keeps the original canvas framing; there is no per-frame crop that could make the fighter jitter. `--ping-pong` appends reversed motion without repeating endpoint frames, nearly doubling the duration to avoid a loop-boundary jump. Omit it when the generated motion already loops cleanly, as with these matched-endpoint clips.

Defaults: `--threshold 12 --feather 8 --height 480 --fps 12 --quality 90`. Increase the threshold cautiously for compressed backgrounds: black outlines that connect to the background can also be removed. Output validation checks alpha and encoded duration. Existing output files require `--force` to replace, and input files cannot be overwritten. PNG/JPEG inputs are also supported for still-image checks; `fighter-blue-transparent.webp` and `fighter-red-transparent.webp` are transparent static fallbacks.

`--speed` controls frame timing independently of frame sampling (`--fps`). Its default is 1 (original speed); `--speed 1.5` displays the same frames 50% faster, while `--speed 2` doubles playback speed. Cumulative timestamps keep millisecond rounding from drifting over the loop. Add `--force` to regenerate existing outputs.

Regression checks:

```powershell
python -m unittest discover -s DevTools/tests -p test_fighter_transparency.py
```

Generation prompt set used with the built-in image tool:

- Courtyard: remove both martial artists and shadows from the prototype composite; reconstruct obscured floor and columns; preserve wide composition, temple, banners, mountains, lake, clouds, and rough flat illustration style; no people or UI.
- Blue: isolate/recreate the left prototype fighter with a blue tunic, brown belt/pants, blank tan face, wrapped shins and fists; full body facing right in a guarded stance; center on uniform pure black with space around both feet; no scenery, shadow, glow, or text.
- Red: mirror the blue fighter to face left and change blue fabric to red; preserve matching proportions, pose, blank face, rough outlines, brown clothing, full-body framing, and pure black backdrop.

Original reference generation prompt:

> Use case: stylized-concept. Asset type: wide background illustration for a browser fighting card game. Create a cinematic 2.7:1 wide composition, inked painterly fighting game key art. Ancient ruined temple columns at dusk, distant mountains, amber sky, dark slate stone platform. Two original martial artists facing each other: on the far left a powerful woman with tied black hair, crimson sleeveless training outfit, wrapped fists in a guarded stance facing right, red energy brushwork; on the far right a lean male with short windswept dark hair, dark violet layered training outfit and wrapped fists facing left, violet energy brushwork. Fighters occupy outer left and right thirds, seen from thighs up, leave the entire middle third as relatively quiet scenery for two UI cards. Dramatic strong silhouettes, hand-painted comic ink, high contrast, red and violet rim lights. Top 20 percent dark quiet sky for health bar overlay. No text, no logos, no cards, no interface, no watermark. The image is decorative static arena art; all game UI will be real HTML layered over it.
