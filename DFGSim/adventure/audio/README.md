# The First Courtyard soundtrack

Eleven instrumental compositions generated with ElevenLabs Music (`music_v1`) using `ELEVENLABS_API_KEY` only in the offline generation command. The key is never written to files or delivered to browsers. Exact prompts, durations and model settings are in `generation-manifest.json`; provider song/request IDs are in the receipts. `masters/` preserves the unprocessed provider MP3s. Delivered tracks are normalized with FFmpeg to -20 LUFS, maximum -2 dBTP, 44.1 kHz, 160 kbps MP3.

Seven area themes: courtyard/home, sanctuary interior, clay market, canal gardens, cedar grove, training terrace, temple court. Four opponent themes: Iddin, Imdugud, Huwawa, Bull of Heaven. Area themes request 80 seconds; champion themes request 64 seconds. The soundtrack shares D dorian, lyre/lute, reed winds, frame drums, and an invitation motif. This is original fantasy music inspired by the setting, not a historical reconstruction.

Generate missing assets without regenerating successful jobs:

```powershell
node DevTools/generate-dfg-soundtrack.mjs
node DevTools/generate-dfg-soundtrack.mjs --only sanctuary
```

Generation stops on provider errors without automatic paid retries. Existing delivery files are skipped; a saved master can be normalized again without another generation. Run only when explicitly authorized to spend generation allowance.

The browser uses a shared Web Audio transport. Music is opt-in, with a saved on/off preference and volume; a user gesture unlocks audio. Incoming themes fade in while the old recording fades out over 2.5 seconds. Area changes settle for 1.6 seconds before switching, so passing a boundary does not thrash themes. A 2-second overlap hides recording loop boundaries. The tab fades to silence when hidden. Asset errors preserve the current theme and show a retry notice, rather than interrupting gameplay.

Adventure combat runs the existing fight page in a same-origin frame. Both pages share the parent's AudioContext and transport: no extra player, no restart across arena transitions. Standalone adventure fight URLs also receive music controls. Frame removal releases its controls from the transport. The classic duel and archived roguelike do not load the soundtrack.

Preview every track at `DFGSim/adventure/soundtrack.php`.

Validation: all eleven delivered MP3s decode as stereo audio and total approximately 13.62 minutes. FFmpeg analysis confirms consistent loudness (-22 to -18 LUFS) and peak headroom below -0.5 dBTP. Chromium integration checks cover playback, looping, interrupted theme selection, mute/unmute, volume persistence, shared arena transport, resuming fights, and defeat-to-sanctuary transitions. The local WebKit runtime has no Web Audio implementation: unavailable controls are checked, but Safari audio playback cannot be certified by that build. Desktop/mobile adventure and archived roguelike browser flows pass in Chromium and WebKit.
