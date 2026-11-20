# Core combat effects

23 ElevenLabs `eleven_text_to_sound_v2` effects for adventure fights. Exact prompts, durations, normalization and output format are in `generation-manifest.json`. Generated with the existing offline `DevTools/generate-azuki-sounds.mjs` using `--manifest DFGSim/adventure/audio/effects/generation-manifest.json`; the environment key never enters client code. Delivery files are 44.1 kHz, 160 kbps MP3, normalized toward -22 LUFS with a -5 dBTP ceiling. The generator skips existing outputs.

The pack covers card play/return, commitment/reveal, bonus/reaction burns, three damage strengths, blocks, earned/spent advantage, barrier absorption/shatter, break checkpoints, burst breakthroughs, confirm marks/cash-in, reaction/round readiness, knockout, victory and defeat.

`combat-sounds.js` classifies public observed transitions. Local card selection plays at the start of card movement. Commitment plays only after a successful local action. Opponent plans produce no effect until both commitments are public. Damage and armor use the same accepted resolution update as fighter reactions and meters. Both impact sounds share their scheduled time with subtle opposing stereo positions. A knockout replaces its seat's normal impact. A concession has an outcome flourish without a fictional impact. Outcome flourishes follow resolved hits by 650 ms. First load and repeated revisions are silent.

Effects use a separate gain bus and saved mute/volume preference in the existing persistent AudioContext. Music can stay off while effects play, or vice versa. Effects start after a user gesture, cache decoded samples, vary ordinary playback rate by ±3%, apply a 75 ms per-effect/seat cooldown, and cap concurrent voices at six. Major impacts and outcomes can replace low-priority clicks. Late asset loads are discarded to avoid delayed unrelated sounds. Hidden tabs mute effects and stop outstanding voices. Frames share the parent mixer rather than creating a second player.

Preview all clips at `adventure/soundtrack.php#effects`. Enemy-specific embellishments and overworld footsteps/doors remain future work.

Checks:

```powershell
node --test DFGSim/adventure/combat-sounds.test.mjs
node DFGSim/adventure/verify-audio.mjs
node DFGSim/adventure/verify.mjs
```

The transition suite covers hidden choices, repeat updates, public burns, both-seat damage, armor/marks/advantages, knockouts and concessions. Browser checks decode actual audio, exercise independent controls and card sounds in the real arena. This local WebKit build omits Web Audio; its unavailable controls and UI flows are checked, while audio playback is exercised in Chromium.
