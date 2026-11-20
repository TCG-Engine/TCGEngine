# Actual ElevenLabs SFX model v3 test

Four successful calls to `/v1/sound-generation` explicitly using `model_id: eleven_text_to_sound_v3`. Prompt: `Card game placing card on playmat`. Requested duration: 1.5 seconds. Prompt influence: 0.87. MP3 44.1 kHz / 128 kbps. Raw provider files are retained without editing or normalization. This reproduces the visible prompt/model and approximate length from the user's example; the web UI's optional enhancement setting is not reproduced by this request.

Preview at `DFGSim/adventure/direct-sounds.php`. `validation.json` records durations and hashes; all four outputs are distinct.

Earlier `pilot-v3/` is named for our third design iteration and still used **SFX model v2**. It must not be mistaken for this model-v3 test. All preceding packs remain historical comparisons, not evidence of v3 quality. New DFG SFX experiments should explicitly select `eleven_text_to_sound_v3` unless the user requests a model comparison; do not silently fall back to v2.
