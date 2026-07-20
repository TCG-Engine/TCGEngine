# AzukiSim RL Bot Models

Published checkpoints used by the in-game Azuki RL bot live here.

- `raizan-lite-v2.json` is the current runtime model for Raizan mirror bot games.
- `selected-model.txt` can point the local runtime at a specific model file in this folder.
- To swap models, edit `selected-model.txt` to one of the published JSON filenames. Delete it or leave it invalid to fall back to `raizan-lite-v2.json`.
- Training runs may continue to write transient checkpoints under `DevTools/rl/artifacts/runs`.
- To publish a new playable bot model, copy the chosen training checkpoint here and either update `selected-model.txt` locally or keep the filename stable if deploying a fixed model path.

## Compiled PHP runtime bundle

The live bot prefers an OPcache-friendly compiled bundle when one matches the
selected JSON checkpoint. Build it after copying or replacing a model:

```powershell
php DevTools\rl\compile_checkpoint_php.php --checkpoint AzukiSim\Models\RLBot\<model>.json
```

The exporter streams the checkpoint instead of decoding the full JSON. It writes:

- `<model>.json.php`: a small compiled-model manifest pointer.
- `Compiled/<model>/<checkpoint-hash>/shards/*.php`: hash-partitioned state/logit arrays.

The runtime hashes the requested state key and requires only its matching shard.
The manifest and loaded shards can be shared through OPcache, while each request
avoids loading and scanning the complete checkpoint JSON. The runtime verifies
the bundle using checkpoint size plus a first/last-4-KB fingerprint. A missing or
stale bundle automatically falls back to the JSON loader.

Deploy the JSON, its `.json.php` manifest, and the referenced `Compiled/` hash
directory together. Re-run the exporter after every model replacement. If the
server disables OPcache timestamp validation, invalidate/reset OPcache as part
of deployment so the stable manifest filename is refreshed; shard directories
are content-addressed and therefore never overwritten in place.

Fresh `compact-v4` models use opponent target profiles (current attack,
remaining HP, and threat) instead of opposing card IDs. Threat is `1` by default;
future per-card overrides are configured in `AzukiRlBotCardThreatValue()` in
`AzukiSim/Custom/GameLogic.php` before training and inference.
