# SFX v3 auditions

Four original takes for each of the 22 remaining core effects: 88 files. `generation-manifest.json` records exact prompts and requests. All use `eleven_text_to_sound_v3`, prompt influence 0.87, a 1.5-second requested length, MP3 44.1 kHz / 128 kbps, an empty style prefix, and no normalization. Prompts are intentionally short game-action descriptions. These files are raw provider outputs.

Review at `DFGSim/adventure/sound-options.php`. Candidates are grouped by action; each is numbered 1–4. Selection and timing edits happen after user feedback. No default winners are assigned, and these candidates do not replace live effects automatically.

The already approved card-play sound is the 0.44-second edit of `sfx-model-v3/playmat-1-v3.mp3`. It is wired into the adventure mixer as `card-swipe`, without random pitch/speed variation; provenance is in `../approved.json`. All other live sounds retain their prior assets until the user selects a replacement.
