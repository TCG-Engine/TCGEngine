# Natural-foley pilot

Five preview candidates responding to feedback that the first pack sounded synthetic and did not match the actions. Prompts describe specific physical materials and gestures rather than abstract game effects. These are ElevenLabs-generated foley imitations, not recordings of actual performers.

`generation-manifest.json` records the exact prompts and settings. `*-master.mp3` preserves the unprocessed provider outputs; `*-v2.mp3` contains the preview edit. `editing.json` records each trim and gain. Leading quiet is trimmed, each excerpt is bounded to one short action, peak levels are matched around -8 dBFS, and a 12 ms end fade avoids an abrupt cut. No synthesized layers or reverb are added.

Review at `DFGSim/adventure/effect-pilot.php`, with previous clips alongside each candidate. None of these candidates is wired into the live sound bank yet. User listening feedback should decide the direction before generating a full replacement pack.
