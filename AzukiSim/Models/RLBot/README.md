# AzukiSim RL Bot Models

Published checkpoints used by the in-game Azuki RL bot live here.

- `raizan-lite-v2.json` is the current runtime model for Raizan mirror bot games.
- `selected-model.txt` can point the local runtime at a specific model file in this folder.
- To swap models, edit `selected-model.txt` to one of the published JSON filenames. Delete it or leave it invalid to fall back to `raizan-lite-v2.json`.
- Training runs may continue to write transient checkpoints under `DevTools/rl/artifacts/runs`.
- To publish a new playable bot model, copy the chosen training checkpoint here and either update `selected-model.txt` locally or keep the filename stable if deploying a fixed model path.
