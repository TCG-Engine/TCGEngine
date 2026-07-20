# TCGEngine RL MVP

This folder contains a minimal deterministic self-play RL prototype for supported TCGEngine roots.
It currently supports `GrandArchiveSim` and `AzukiSim`.

See `STATE_REPRESENTATION_NOTES.md` for current Azuki tabular state-key findings and compression ideas.

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

The coordinator owns the live policy and writes checkpoints. For each batch, it snapshots the current policy, starts up to `--workers` PHP worker processes, lets each worker run one or more episodes from that frozen policy, then merges the returned sparse policy deltas. Use `--workers 1` or omit the flag for the original sequential trainer.

Workers can run multiple consecutive episodes from one policy load with `--worker-episodes`:

```bash
php DevTools/rl/train_selfplay_php.php --root AzukiSim --deck-file DevTools/rl/azuki_raizan.txt --episodes 1000 --seed 126 --max-steps 500 --checkpoint-every 50 --log-every 10 --memory-only --workers 8 --worker-episodes 4
```

In this mode, the coordinator writes one frozen policy snapshot per batch, each worker loads it once, runs up to `--worker-episodes` episodes locally, and returns one merged sparse delta. This intentionally makes policy updates less frequent, but avoids repeatedly decoding the full policy for every single episode.

Experimental two-tier strategy mode is available with `--strategy-mode aggro-control`:

```bash
php DevTools/rl/train_selfplay_php.php --root AzukiSim --deck-file DevTools/rl/azuki_raizan.txt --episodes 1000 --seed 126 --max-steps 500 --checkpoint-every 50 --log-every 10 --memory-only --workers 8 --worker-episodes 4 --strategy-mode aggro-control
```

This stores a small strategic posture table inside the same checkpoint as the normal action table. The strategic table uses a tiny Azuki state summary, currently life buckets plus attack pressure, and chooses between `aggro` and `control` using the same terminal reward as the action policy. The chosen posture only filters obvious target decisions: `aggro` prefers leader targets when present, while `control` prefers opposing unit targets when present. If no matching target exists, the normal tactical policy sees the full legal action set.

When `--strategy-mode aggro-control` is enabled, the tactical action table uses posture-shaped immediate rewards plus a small terminal component. The strategic table still uses only terminal win/loss reward.

Default tactical shaping:

- `--tactical-terminal-weight 0.1`: tactical actions still receive 10% of final terminal reward.
- `--aggro-leader-damage-reward 0.25`: aggro posture rewards damage dealt to the opposing leader.
- `--control-leader-damage-reward 0.05`: control posture lightly rewards damage dealt to the opposing leader.
- `--control-enemy-threat-reward 0.15`: control posture rewards permanent reduction of opposing board attack.
- `--control-own-threat-penalty 0.1`: control posture penalizes permanent loss of friendly board attack.
- `--tactical-no-state-change-penalty 0.05`: no-op/failed tactical actions receive a small penalty.
- `--tactical-unused-ikz-penalty 0.02`: passing the main phase while an affordable hand play exists loses 0.02 per unused IKZ, capped at 0.2.

Episode replays include `strategyPosture` and `tacticalReward` fields for shaped tactical steps.

Attack shaping is transaction-aware. Declaring an attack records the attacker,
posture, and pre-attack strategy snapshot. Damage and board changes that occur
after the defending player's response window closes are attributed back to that
attack declaration; the pass that merely commits combat receives no combat
reward. Replay attack steps include `combatRewardResolvedAtStep`, and the
resolving response step includes `combatRewardAttributedToStep`.

Fresh Azuki training uses the context-gated `AzukiSim:compact-v4` state and
`semantic-v2` action keys. It retains IKZ availability, hand and life buckets,
then includes board and legal-action summaries only in the contexts where they
matter. This avoids the cross-product state growth seen in `compact-v2`. Logits
are keyed by stable meanings such as
`play:<cardID>`, `attack:<cardID>`, and
`target:<decision>:<role>:atk=<attack>:hp=<remainingHP>:threat=<threat>`, plus
distinct main and response passes instead of by legal-list position. Own-card
actions retain exact card IDs; opposing targets use their current attack,
remaining HP, and threat profile so target learning transfers across decks.

Threat defaults to `1` for every card. Future per-card overrides belong in
`AzukiRlBotCardThreatValue()` in `AzukiSim/Custom/GameLogic.php`.

Legacy `lite-v2`/index, `compact-v2`, and `compact-v3`/`semantic-v1`
checkpoints remain loadable for evaluation and continued same-version training,
but they do not migrate into the v4 table. Omit `--checkpoint` when starting the
v4 generation from a fresh policy.

To compile a published checkpoint for low-memory live inference:

```powershell
php DevTools\rl\compile_checkpoint_php.php --checkpoint AzukiSim\Models\RLBot\<model>.json
```

This produces a small `<model>.json.php` manifest and content-addressed PHP
shards under `AzukiSim/Models/RLBot/Compiled/`. The in-game bot prefers a valid
compiled bundle and requires only the shard for the current state; it falls back
to JSON when the bundle is absent or does not match the selected checkpoint.
The exporter itself parses the checkpoint as a stream, so compiling a large
model does not require a second model-sized in-memory representation.

PHP trainer artifacts:

- `checkpoints/*.json`: tabular policy snapshots for evaluation or later reuse. Controlled by `--checkpoint-every`; the final episode always writes a checkpoint. Checkpoints are streamed to disk so saving does not allocate a second model-sized JSON string.
- `replays/episode_*.json`: exact initial gamestate plus chosen actions for the final episode only, used for regression conversion and UI debugging.
- `replays/timeout_episode_*.json`: exact initial gamestate plus chosen actions for the first timed-out episode, only written when a timeout occurs.
- `run_config.json`: run arguments, final throughput summary, and per-episode summaries.
- `workers/*.json`: coordinator/worker scratch files for the active parallel batch. Successful batches delete these files after their deltas and replays are merged; failed-batch files are retained for diagnosis. Parallel mode does not retain unused in-memory frozen checkpoint copies.

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
