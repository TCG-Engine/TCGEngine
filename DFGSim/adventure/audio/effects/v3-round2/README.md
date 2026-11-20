# Second SFX v3 audition round

Four new original takes for each of ten rejected groups: commit, bonus burn, light hit, medium hit, damage mark, confirmed damage, new round, knockout, victory, defeat. Exact short prompts and durations are in `generation-manifest.json`. All use `eleven_text_to_sound_v3` with prompt influence 0.87, an empty style prefix and no normalization. Bonus burns request 0.7 seconds and a short fiery whoosh without sizzle; combat and outcome prompts explicitly say fighting game. Damage-mark prompts avoid card handling.

The user's twelve selections from Round 1 are recorded in `../approved.json` alongside the previously selected card-play sound. The selected assets live in `../selected/`, trimmed around their active portions with 6 ms tail fades; card return is bounded to 0.55 seconds. Block take 3 has +6 dB gain. Other selected source levels are unchanged. Selected sounds play at their original speed/pitch. All original takes are preserved.

The map and standalone adventure arena embed the same approved manifest for the persistent mixer. The audition gallery (`DFGSim/adventure/sound-options.php`) places unselected groups first, shows Round 2 takes 1–4, preserves Round 1 underneath, and highlights the applied choices in selected groups. Medium-hit take 1 was described as audible but not chosen, so it remains only an earlier comparison.

Mark damage warns of pending next-round follow-up damage. Confirm damage is its payoff when the next attack connects, before armor. These effects are optional feedback for existing mechanics; this revision does not change the mechanics.
