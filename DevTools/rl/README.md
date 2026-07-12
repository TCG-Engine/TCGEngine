# TCGEngine RL MVP

This folder contains a minimal deterministic self-play RL prototype for supported TCGEngine roots.
It currently supports `GrandArchiveSim` and `AzukiSim`.

## Grand Archive deck text format

Use the same free-text format already supported by `GrandArchiveSim/Custom/DeckTextParser.php`:

```text
# Material Deck
1 Spirit of Serene Fire
1 Some Regalia

# Main Deck
4 Card Name A
4 Card Name B
...
```

## Azuki deck text format

For `--root AzukiSim`, the deck file can contain one starter deck name:

```text
Raizan
```

Supported starter names are `Raizan`, `Shao`, `Bobu`, and `Zero`. A thegateikz.com deck slug or URL can also be used, in which case AzukiSim's normal deck importer is used.

## Train

```bash
python DevTools/rl/train_selfplay.py --root GrandArchiveSim --deck-file path/to/deck.txt --episodes 100 --seed 123 --max-steps 1000
```

Azuki starter example:

```bash
python DevTools/rl/train_selfplay.py --root AzukiSim --deck-file DevTools/rl/azuki_raizan.txt --episodes 10 --seed 123 --max-steps 200
```

Artifacts are written under `DevTools/rl/artifacts/runs/<run_id>/`.

## Fast PHP trainer

The current tabular trainer is also available as a PHP in-process runner:

```bash
php DevTools/rl/train_selfplay_php.php --root AzukiSim --deck-file DevTools/rl/azuki_raizan.txt --episodes 10 --seed 123 --max-steps 200
```

This uses the same bridge legality/action helpers, but it calls them directly inside one PHP process instead of making one Python-to-PHP stdio round trip per action. Artifacts are written under `DevTools/rl/artifacts/runs/<run_id>/`.

Parallel coordinator/worker mode is available with `--workers`:

```bash
php DevTools/rl/train_selfplay_php.php --root AzukiSim --deck-file DevTools/rl/azuki_raizan.txt --episodes 1000 --seed 126 --max-steps 500 --checkpoint-every 50 --log-every 1 --memory-only --workers 8
```

The coordinator owns the live policy and writes checkpoints. For each batch, it snapshots the current policy, starts up to `--workers` PHP worker processes, lets each worker run one episode from that frozen policy, then merges the returned sparse policy deltas. Use `--workers 1` or omit the flag for the original sequential trainer.

The tabular policy uses a coarse scalar state key. Current `lite-v2` keys include active/turn player, phase, own hand count, existing resource/material count fields, exact leader/champion life and damage, and each player's next decision type. They intentionally omit exact turn number, deck counts, opponent hand count, and exact decision queue counts to reduce table growth. Older checkpoints with legacy state keys can still be passed as `--checkpoint`, but their incompatible logits are discarded and training starts a fresh `lite-v2` table with the same trainer settings.

PHP trainer artifacts:

- `checkpoints/*.json`: tabular policy snapshots for evaluation or later reuse. Controlled by `--checkpoint-every`; the final episode always writes a checkpoint.
- `replays/episode_*.json`: exact initial gamestate plus chosen actions for the final episode only, used for regression conversion and UI debugging.
- `replays/timeout_episode_*.json`: exact initial gamestate plus chosen actions for the first timed-out episode, only written when a timeout occurs.
- `run_config.json`: run arguments, final throughput summary, and per-episode summaries.
- `workers/*.json`: coordinator/worker scratch files for parallel runs.

The PHP trainer intentionally does not write CSV timing/metrics files. Progress and throughput are printed to the console, while `run_config.json` keeps the compact final summary.

Timeouts default to reward `-0.25` in the PHP trainer, so max-step caps are treated as failed trajectories instead of neutral draws. To tune that penalty:

```bash
php DevTools/rl/train_selfplay_php.php --root AzukiSim --deck-file DevTools/rl/azuki_raizan.txt --episodes 1000 --seed 126 --max-steps 500 --checkpoint-every 50 --log-every 1 --timeout-reward -0.25
```

To continue training from a previous PHP checkpoint, pass `--checkpoint` and start from a new seed range:

```bash
php DevTools/rl/train_selfplay_php.php --root AzukiSim --deck-file DevTools/rl/azuki_raizan.txt --checkpoint DevTools/rl/artifacts/runs/<run_id>/checkpoints/latest.json --episodes 1000 --seed 1126 --max-steps 500 --checkpoint-every 50 --log-every 1
```

For example, a 1000-episode run that started at seed `126` used episode seeds `126..1125`, so a follow-up run can use `--seed 1126`.

## Evaluate

```bash
python DevTools/rl/eval_selfplay.py --root GrandArchiveSim --deck-file path/to/deck.txt --checkpoint DevTools/rl/artifacts/runs/<run_id>/checkpoints/latest.json --matches 50 --seed 123 --max-steps 1000
```

## Replay an episode into `Games/<gameName>` for UI debugging

```bash
python DevTools/rl/replay_episode_to_game.py --root GrandArchiveSim --episode-file DevTools/rl/artifacts/runs/<run_id>/replays/episode_0001.json --deck-file path/to/deck.txt --game-name rl_debug_0001
```

Optional partial replay:

```bash
python DevTools/rl/replay_episode_to_game.py --root GrandArchiveSim --episode-file ... --deck-file path/to/deck.txt --game-name rl_debug_0001 --steps 15
```

## Future work: Python bridge speed

The Python trainer is useful if the policy later moves to Python ML libraries, but the current bridge is limited by one synchronous stdio request per action. Possible improvements:

- Add a batch rollout command so PHP can apply several policy-selected actions before returning.
- Add compact training payloads that omit verbose card metadata, replay fields, and full summary data when they are not needed.
- Let Python request only legal-action keys plus scalar observation fields during training, with full snapshots reserved for replay/debug runs.
- Run several PHP daemon workers in parallel, one per game, and aggregate trajectories in Python.
- Keep an optional PHP-side rollout loop that receives a policy/checkpoint from Python, runs full episodes in-process, and returns trajectories.
- Add timing counters for JSON encode/decode and stdio read/write separately so bridge overhead can be tracked independently from engine apply/snapshot/enumerate time.
