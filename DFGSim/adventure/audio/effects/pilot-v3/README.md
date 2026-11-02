# Sound in Motion pilot

Seven new ElevenLabs elements: card motion, card landing, reveal, strike contact, strike body, deflection, and burn. Original prompts/model/settings are in `generation-manifest.json`. Unprocessed provider responses are `*-master.mp3`; `editing.json` records leading trim, bounded event duration, and gain. Edited WAV elements peak near -10 dBFS, with 8 ms tail fades. These are stylized original assets; no Runeterra audio is reused.

`sequence.json` defines a 7.7-second design vignette: cards moving and landing, reveal, burn, landed strike, then a second reveal and deflection. The hit has separate contact/body layers. `exchange-v3.mp3` mixes those events; `exchange-v1.mp3` places the earlier pack on the same timeline for comparison. Both are limited to 0.5 linear peak, without automatic make-up gain.

`sound-exchange.php` uses the existing courtyard art and fighter reaction clips. Visuals follow the media playhead rather than independent sound timers. Replay and version switching stop the previous run, hidden tabs stop the preview, and reduced-motion users get static poses without lunges/flashes. This is an audio timing study, not a simulated game-state outcome. The live fight sound bank remains unchanged pending user listening feedback.
