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

An AzukiDeck deck can also be loaded directly by its numeric deck number (the `gameName` in an AzukiDeck editor URL). Use `--deck` instead of creating a text file:

```bash
php DevTools/rl/train_selfplay_php.php --root AzukiSim --deck 51 --episodes 10 --seed 123 --max-steps 200
```

The full editor URL is accepted too:

```bash
php DevTools/rl/train_selfplay_php.php --root AzukiSim --deck "https://zendo.gg/TCGEngine/NextTurn.php?gameName=51&playerID=1&folderPath=AzukiDeck" --episodes 10
```

The trainer converts the editor link to the dedicated AzukiDeck `AzukiDeck/LoadDeck.php` JSON endpoint. Set `TCGENGINE_AZUKIDECK_API_URL` to override that endpoint for local development. If the API is unavailable and the deck exists under the local `AzukiDeck/Games` directory, the PHP trainer falls back to the local saved deck state.

A deck text file containing only the number or URL also works. For compatibility, a number or URL may be supplied directly as the value of `--deck-file`, although `--deck` is clearer.

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

Zero residual-policy training delegates choices covered by the deterministic
Zero heuristics and updates the tabular policy only for choices where those
heuristics abstain:

```powershell
php DevTools/rl/train_selfplay_php.php --root AzukiSim --deck 51 --episodes 20000 --seed 126 --max-steps 500 --checkpoint-every 250 --log-every 100 --memory-only --workers 8 --worker-episodes 4 --heuristic-policy zero --train-fallback-only
```

Residual checkpoints are tagged with `policy_role: residual` and
`heuristic_policy: zero`. Replays mark every action with `heuristicCovered` and
`heuristicRule`; run summaries report heuristic and fallback step counts. A
fallback run can be continued with `--checkpoint`, but only from another Zero
residual checkpoint using the current `AzukiSim:compact-v4` / `semantic-v2`
representation. Fallback-only mode is intentionally incompatible with
`--strategy-mode`.

Bobu Deck 241 uses the same residual-policy pipeline with its dedicated
midrange/control heuristic:

```powershell
php DevTools/rl/train_selfplay_php.php --root AzukiSim --deck 241 --episodes 20000 --seed 126 --max-steps 500 --checkpoint-every 250 --log-every 100 --memory-only --workers 8 --worker-episodes 4 --heuristic-policy bobu --train-fallback-only
```

Bobu residual checkpoints are tagged with `heuristic_policy: bobu`; only
decisions on which the Bobu policy abstains update the residual table.

To train Bobu specifically against the aggressive Zero Deck 51 opponent, add
an asymmetric fixed opponent. Player 1 uses Bobu's heuristic plus the trainable
residual policy; Player 2 uses Zero's deterministic heuristic, and its choices
are excluded from the Bobu policy update:

```powershell
php DevTools/rl/train_selfplay_php.php --root AzukiSim --deck 241 --opponent-deck 51 --episodes 150000 --seed 126 --max-steps 750 --checkpoint-every 2500 --log-every 320 --memory-only --workers 8 --worker-episodes 4 --heuristic-policy bobu --opponent-heuristic-policy zero --train-fallback-only
```

If the fixed Zero heuristic abstains, its unmodeled choice is sampled from an
untrained opponent policy. Those opponent choices never update or contaminate
the Bobu residual checkpoint. Omitting `--opponent-deck` preserves symmetric
same-deck training.

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
- `replays/timeout_episode_*.json`: exact initial gamestate plus chosen actions for the first episode that reaches `--max-steps` or `--max-turns`.
- `replays/failure_episode_*.json`: exact initial gamestate plus chosen actions for the first episode that cannot continue because it has no legal actions, all actions were filtered, or the engine threw an error.
- `run_config.json`: run arguments, final throughput summary, and per-episode summaries.
- `workers/*.json`: coordinator/worker scratch files for the active parallel batch. Successful batches delete these files after their deltas and replays are merged; failed-batch files are retained for diagnosis. Parallel mode does not retain unused in-memory frozen checkpoint copies.

The PHP trainer intentionally does not write CSV timing/metrics files. Progress and throughput are printed to the console, while `run_config.json` keeps the compact final summary.

Incomplete trajectories default to reward `-0.25` in the PHP trainer. This applies to genuine max-step/max-turn timeouts and separately reported engine/action failures instead of treating either as a neutral draw. To tune that penalty:

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

## Runtime performance comparison

Compare two prepared local runtimes with the same deck, seeds and episode limits:

```bash
python3 DevTools/rl/benchmark_runtime.py --baseline /path/to/baseline --episodes 20 --repeats 3 --output /tmp/rl-performance.json
```

The candidate defaults to this checkout. Both runtimes need their generated files
and local card dictionaries. The tool alternates execution order, retains trainer
artifacts and logs, reports median steps/second and total run time, and rejects
runs with engine/action failures. Timing uses a monotonic clock and includes PHP
startup and final checkpoint writing; the trainer's own timing is retained separately. It also records timeouts and total steps: rules changes can change action
paths even with identical seeds, so throughput is not a like-for-like latency test.
The requested memory-only mode falls back to disk when APCu is unavailable; the
report records the actual storage mode from the replay. Compare on the same machine
without competing training jobs. For Azuki, add `--root AzukiSim --deck-file
DevTools/rl/azuki_raizan.txt`.

`benchmark_control_ga.txt` is a synthetic Grand Archive control deck with repeated
vanilla allies, intended to keep action paths comparable across targeting changes.
It is not a tournament-legal deck. The report includes the final replay action hash
so equal step counts can also be checked against the recorded decisions.

## Grand Archive: learn against frozen archetype bots

`train_ga_tournament.php` runs a **tabular terminal-reward learner** against a
frozen copy of the current Grand Archive heuristic. It is an offline experiment;
it does not replace or publish the live bots. It uses the current GA bot action
catalog, including its bounded/default choices for complex prompts. Learning
cannot discover alternatives that catalog does not offer.

Snapshot the selected menu decks and opponent once:

```bash
python3 DevTools/ga-bot-tournament-setup.py /tmp/ga-rl-roster
php DevTools/rl/train_ga_tournament.php \
  --config /tmp/ga-rl-roster/config.json --baseline /tmp/ga-rl-roster/baseline \
  --output /tmp/ga-rl-training-001 --games 10
```

Continue from the checkpoint, advancing the schedule by ten:

```bash
php DevTools/rl/train_ga_tournament.php \
  --config /tmp/ga-rl-roster/config.json --baseline /tmp/ga-rl-roster/baseline \
  --output /tmp/ga-rl-training-002 --start 10 --games 10 \
  --checkpoint /tmp/ga-rl-training-001/latest.json
```

The round-robin covers each unordered pair in both deck roles and both learner
seats (840 games for 21 decks). Adjacent games swap seats with the same starting
seed; exploration means they need not follow identical paths. Subsequent cycles
use new seeds. Ten games is a review/checkpoint interval, not a strength claim.
Policy updates occur after each completed game; checkpoints are saved every ten
and at the final game. Keep the roster, baseline and training seed fixed when
continuing; the global `--start` offset advances seeds as well as matchups.
Use a durable directory instead of `/tmp` for long-running experiments.

Evaluate separately on held-out seeds, without applying updates:

```bash
php DevTools/rl/train_ga_tournament.php \
  --config /tmp/ga-rl-roster/config.json --baseline /tmp/ga-rl-roster/baseline \
  --output /tmp/ga-rl-evaluation-001 --games 10 --evaluate \
  --checkpoint /tmp/ga-rl-training-002/latest.json
```

Default training/evaluation seeds start at 10001/1000001. Explicit `--seed`
values must keep those sets disjoint. Evaluation samples the learned categorical
policy without epsilon exploration and asserts its checkpoint stays unchanged.
For progress comparisons, evaluate earlier and later checkpoints on the same
held-out schedule, then confirm gains on a second unseen seed range. Do not
train on evaluation trajectories or promote from a single ten-game result.

Artifacts include `report.json` (matchups, completed games, learner wins, source
and policy hashes), `game-N.json` replays, `checkpoint-N.json`, and `latest.json`.
Timeouts/engine failures remain in reports and replays but do not update the
learner or count as completed wins. Rejected actions are excluded from reward
updates. The opponent's actions never update the learner, in either seat.
Opponent choice code is frozen; the engine and action enumerator are shared.

The learner uses a versioned public-state projection, its own hand/memory and
known deck multiset, plus semantic card/action keys. It never uses hidden deck
order or opponent hand/memory identities. Replays contain full private states
for local debugging and must not be used directly as model inputs. Exact card
and board keys make this a sparse baseline; it may need substantial training
and a more compact/generalizing representation before it beats the heuristics.

Validation:

```bash
php DevTools/rl/test_ga_policy.php
python3 DevTools/rl/test_ga_tournament.py
```

Initial local verification (2026-10-29): ten completed training games, then ten
completed held-out evaluation games. Training changed the checkpoint;
evaluation left it unchanged (4 learner wins / 10). This is a pipeline check,
not evidence of improvement over an untrained policy. Every replay action was
checked against its learner/opponent seat. The unit tests cover semantic keys,
hidden-information exclusion, seat-filtered rewards, and checkpoint round trips;
the integration test covers timeouts, frozen evaluation, and output protection.
An Azuki shared-trainer smoke run was unavailable because that local runtime's
generated card dictionaries are missing.

Compare two evaluations with identical conditions:

```bash
python3 DevTools/rl/compare_ga_evaluations.py \
  /path/to/earlier-evaluation/report.json /path/to/later-evaluation/report.json
python3 DevTools/rl/test_compare_ga_evaluations.py
```

The comparison rejects mismatched seeds, schedules, decks, frozen opponents,
recorded runtime hashes, or action limits, and any evaluation that changed its
policy. It counts improved, regressed, and unchanged outcomes only where both
games completed; incomplete pairs are reported separately. These are descriptive
results, not a significance test or permission to deploy a checkpoint. Older
reports without `maxSteps` must be evaluated again before comparison.

Each game's `policyCoverage` records attempted learner decisions (including
rejected attempts), choices with two or more legal actions, choices in a known
trained state, and choices with a nonuniform learned preference over the legal
actions. Forced moves and equal logits do not count as learned preferences.
Low coverage means most choices still sample uniformly: accumulating checkpoint
weights alone is not evidence that learning transfers to new games.

Follow-up local measurement (2026-10-29): after 20 attempted training games,
18 had completed. The second ten-game batch completed eight, with zero learner
wins; two reached the 1,000-action limit and were excluded from learning.
Matched ten-game evaluations of the 10-game and 20-game checkpoints both
completed all games and won four, with identical outcomes. Both recorded
1,054 multi-action choices and zero known-state or learned-preference choices.
This provides no evidence of improvement: the exact-state table did not
transfer to any evaluated choice. Next work should introduce shared features
across positions (with new checkpoint versioning and hidden-information tests)
before spending substantial time on more exact-state training. Live bots were
not changed. Local reports/checkpoints for this measurement are under
`/tmp/ga-rl-training-next20`, `/tmp/ga-rl-eval-before20`, and
`/tmp/ga-rl-eval-after20`; those paths are temporary, not committed artifacts.

### Compact Grand Archive pilot

The earlier pilot used `GrandArchiveSim:compact-v2`. This remains tabular
REINFORCE, using coarse shared situations rather than a neural network or
independent linear features. It groups champion HP into critical (≤5), low
(6–12), and higher ranges; board power balance into behind/even/ahead; and own
hand/memory counts into empty/small/larger ranges. Phase, acting turn, prompt
handler/type, and stack presence remain distinct. Different card identities in
the board or hand can now share learned preferences. Card/ability action keys
remain semantic, so unrelated effects are not collapsed into one action.

This deliberately loses detail: statuses, individual threats, exact resources,
deck probabilities and card synergies are not represented in the compact state.
Legality still comes from the engine. It is an offline generalization experiment,
not a replacement for the heuristic bot's richer knowledge or a claim of strength.
Opponent hidden identities and draw order are excluded.

```bash
php DevTools/rl/train_ga_tournament.php \
  --config /tmp/ga-rl-roster/config.json --baseline /tmp/ga-rl-roster/baseline \
  --output /tmp/ga-rl-compact-pilot --games 100 --max-steps 1000
```

Checkpoint representation is retained when resuming. Use `--representation
public-v1` to start the old exact-state baseline; explicitly requesting a different
representation from a loaded checkpoint is rejected. Start a fresh model to
switch representations. Both versions use `ga-semantic-v1` action keys. Compare
compact checkpoints against an untrained compact model on identical held-out
seeds, recorded runtime, and action limits, using the comparison tool above.
With the current 21-deck circle schedule, the first 42 evaluation games include
all 21 decks as learners, with paired seats, but are not a full round-robin.

Compact pilot measurement (2026-10-29): 100 training attempts produced 93
completed games, 28 learner wins, and seven excluded timeouts. Replay inspection
verified learner-only seat attribution and frozen opponent selection throughout.
On 42 held-out games covering all 21 learner archetypes in both seats, the
untrained compact model won 12 (37 completed), and the trained model won 11
(38 completed). Of 36 mutually completed pairs, two improved, four regressed,
and 30 were unchanged; six pairs had at least one incomplete game. The trained
model used nonuniform learned preferences on 4,388 of 6,107 choices (71.85%),
versus zero for the untrained model. Generalization now occurs, but this pilot
shows no strength improvement and does not justify scaling or promotion.
Consider a richer feature model and heuristic-guided initialization before a
larger run; validate those changes on a new held-out seed range, since this
range has now informed development. The coarse representation can conflate
positions with importantly different tactics.

Temporary local artifacts: `/tmp/ga-rl-compact-pilot100`,
`/tmp/ga-rl-compact-eval-untrained42`, `/tmp/ga-rl-compact-eval-trained42`, and
`/tmp/ga-rl-compact-comparison42.json`. Training checkpoints exist at every ten
games. Tests: 16 policy checks, the tournament integration check (including
representation mismatch rejection), and three comparison tests passed. Live
bots and their deployed decision policies were not changed by this pilot.

### Heuristic-guided initialization

Use `--imitate` for a separate supervised warm-start phase. The frozen heuristic
chooses the learner's actions; accepted learner trajectories maximize the teacher
action's log-likelihood with unit reward regardless of the winner. Opponent
trajectories and incomplete games remain excluded. This phase is imitation, not
RL. Remove `--imitate` when continuing from its checkpoint to run ordinary
terminal-reward RL. `--imitate --evaluate` is rejected.

```bash
php DevTools/rl/train_ga_tournament.php \
  --config /tmp/ga-rl-roster/config.json --baseline /tmp/ga-rl-roster/baseline \
  --output /tmp/ga-rl-imitation --imitate --games 42 --seed 20001
php DevTools/rl/train_ga_tournament.php \
  --config /tmp/ga-rl-roster/config.json --baseline /tmp/ga-rl-roster/baseline \
  --output /tmp/ga-rl-after-imitation --checkpoint /tmp/ga-rl-imitation/latest.json \
  --start 42 --games 10 --seed 20001
```

Evaluate the initialized and RL-trained checkpoints on identical fresh held-out
seeds (for example `--seed 2000001`), without `--imitate`. Imitation checkpoints
are portable learned tables; the teacher is not consulted for learner choices
in ordinary training or evaluation. A small supervised sample does not reproduce
the full heuristic bot. Its richer tactics can be lost in compact-state buckets.
Synthesized teacher multi-selections can be recorded, but those keys are not
selectable later unless the normal legal-action catalog offers them. This is a
remaining action-coverage limitation, not successful imitation of those choices.
Replay learner actions in imitation mode carry `heuristicPolicy: ga-teacher`;
opponent actions remain `ga-frozen`. Coverage counters measure model selections
and consequently stay zero during teacher-driven imitation.

Warm-start measurement (2026-10-30): 42 imitation games completed across all
21 learner archetypes in both seats, using training seeds starting at 20001.
The following ten RL games (schedule start 42) completed nine, with one timeout
excluded from learning. Matched 42-game evaluations on new seeds starting at
2000001 completed 40 games for each checkpoint. Imitation-only won 13; after
RL won 14. Of 40 mutually completed pairs, one improved, none regressed, and
39 were unchanged; two incomplete pairs were excluded. Learned preferences
influenced 3,319/5,484 choices before RL and 3,746/5,694 after. This one-game
advantage is not evidence of a reliable strength gain; confirm on another seed
range before scaling. These results cannot be compared directly with the earlier
untrained pilot, which used different seeds and runtime hashes.

All imitation replays were checked for teacher/learner seat attribution. Both
evaluation checkpoints stayed unchanged, with no teacher-selected learner moves.
Policy checks, tournament integration (including imitation timeout exclusion and
mutual exclusion with evaluation), comparison tests, and PHP syntax checks pass.
Local temporary artifacts: `/tmp/ga-rl-imitation42`, `/tmp/ga-rl-imitation-rl10`,
`/tmp/ga-rl-imitation-eval42`, `/tmp/ga-rl-imitation-rl-eval42`, and
`/tmp/ga-rl-imitation-comparison42.json`. Live bots remain unchanged.

Fresh-seed confirmation (2026-10-30): repeated the same 42-game schedule with
seeds starting at 3000001, leaving both checkpoints frozen. Imitation-only won
12 of 40 completed games (two timeouts); the RL checkpoint won 12 of 39
completed games (three timeouts). Among 39 mutually completed pairs, two
improved, two regressed, and 35 were unchanged. All 84 replay seat assignments
were checked, with no teacher-assisted learner moves and no model updates.
The earlier one-win advantage did not repeat. Across both held-out ranges there
were only three improved and two regressed mutually completed outcomes; this
is not evidence of a reliable gain and does not justify scaling or promotion.
Next work should address tactical state/action representation and timeout causes,
rather than simply increasing training volume for this model.

Temporary confirmation artifacts: `/tmp/ga-rl-confirm-initial42`,
`/tmp/ga-rl-confirm-trained42`, `/tmp/ga-rl-confirm-comparison42.json`.
No model training or live-bot changes were performed during this confirmation.

### Shared-weight GA learning cycle

`linear-v1` is an experimental shared-weight softmax policy. Unlike the older
state/action lookup tables, it transfers action-role preferences and their
interactions with numeric observations between different boards. It uses public
field health/readiness, hand/memory counts, reserve availability, combat threat,
and lower/upper draw probabilities from the player's known deck multiset.
Action descriptors include visible targets and printed reserve costs. Printed
cost margin is a feature, not an engine legality check. The engine remains authoritative.
No opponent hidden identities or draw order enter these observations.

New GA runs default to `linear-v1` (also selectable explicitly); existing checkpoint
versions remain loadable. Linear gradients include the softmax temperature and
are averaged per completed episode. GA exploration now samples the policy itself
rather than mixing an epsilon policy with an uncorrected policy gradient.
Incomplete games still never produce updates.

Run the local workflow (42 imitation games, 10 sequential RL games, a 12-game
paired screen, then two independent 42-game paired evaluations if screening passes):

```bash
python3 DevTools/rl/run_ga_learning_cycle.py \
  --config /path/to/tournament/config.json \
  --baseline /path/to/frozen-baseline \
  --output /path/to/new-experiment
```

Each cycle first runs the startup, policy, Unique-rule, and Tweedledum regression
checks; a failure stops it before training. Label the experiment with
`--hypothesis "Improve weapon timing"` to record its intended change.

Evaluation defaults to two isolated PHP workers (`--workers 2`). Training updates
remain sequential. The screen rejects any incomplete pair or regressed outcome;
ties advance. This is a cheap triage gate, not a strength or significance test.
Screening uses seed range 9100001; final validation uses 9200001 and 9300001.
Do not tune on final validation results; reserve new seed ranges for subsequent
validation after tuning. Seed ranges must not overlap.

Checksummed evaluation results are cached under
`DevTools/rl/artifacts/evaluation-cache` (override with `--cache`). Keys cover the
checkpoint, deck/config contents, frozen opponent, engine/generated code, runner,
PHP version, seed range, and step limit. Worker count is deliberately excluded:
serial and parallel evaluations must reproduce identical decisions. Damaged cache
entries are rerun. Only finished batches are published, including any incomplete
game diagnostics; the screen still rejects incomplete games.

Reuse a teacher with `--teacher-checkpoint /path/to/imitation/latest.json` to avoid
retraining it and reuse its baseline evaluation. Add
`--candidate-checkpoint /path/to/rl/latest.json` to evaluate an existing candidate
without training. For supplied checkpoints, ensure their training seeds are
independent of the chosen screening/validation seeds. `summary.json` records stage
timings, cache hits, screening decisions, comparisons, and failures.

Teacher-fidelity rollout is now an optional diagnostic rather than an automatic
full batch on every cycle; use the standalone tools below with held-out teacher
replays.
Reports compare the imitation model against its RL continuation, both playing
against the frozen heuristic. They do not establish superiority to that opponent
unless win results actually support it. Nothing deploys or promotes a model.

Standalone diagnostics:

```bash
php DevTools/rl/ga_imitation_fidelity.php --checkpoint /path/to/latest.json --replays /path/to/teacher-games
python3 DevTools/rl/ga_timeout_audit.py /path/to/batch
```

Fidelity reports tie-adjusted top agreement, mean teacher probability, and
cross-entropy. Use held-out teacher seeds; training-replay agreement is only a
sanity check. `imitationMetrics.teacherMissing` identifies teacher actions absent
from the catalog before teacher injection. Multi-select prompts now enumerate
up to 2,048 subsets consistently during training and inference, with truncation
marked on larger prompts. This is bounded coverage, not complete combinatorial
search; numeric/split/rearrange prompts retain the existing fallback coverage.

Timeout diagnostics distinguish repeated transitions, mostly rejected actions,
and changing-state step limits. A repeated Buff action with changing states is
not by itself proof of an engine bug. Inspect replays before changing card rules
or awarding outcomes. The cycle preserves incomplete-game exclusions.

Tests: `php DevTools/rl/test_ga_policy.php`,
`python3 DevTools/rl/test_ga_tournament.py`, and
`python3 DevTools/rl/test_compare_ga_evaluations.py`. Feature tests include hostile
opponent hidden-card objects, cross-state transfer, checkpoint roundtrip,
inference immutability, and a finite-difference gradient check.

Initial linear smoke experiment (2026-10-30): 10 imitation games and 10 RL
continuation games completed; all 40 evaluation games across two independent
10-match paired ranges also completed. The imitation checkpoint won 7/20;
the RL continuation won 6/20 (zero improved outcomes, one regression, 19 tied).
No strength gain or promotion is claimed. Teacher top agreement was 77.8% on
training replays and 61.1% on two independent teacher games (271 choices).
These small runs do not cover the full 21-archetype schedule. Use the default
42-game cycle for broader coverage before increasing training volume.
Artifacts are local temporary directories `/tmp/ga-linear-*`, not deployed models.

### Full-archetype linear evaluation (2026-10-30)

Code baseline: `47ae7aed5`. Ran the learning cycle with 42 imitation games,
42 independent teacher-observation games, 42 RL games, and 42 games per
checkpoint on each of two evaluation seeds (294 scheduled, 288 completed).
All 21 archetypes were scheduled in both seats; this is not an all-pairs tournament.

On 80 mutually completed evaluation pairs, the imitation checkpoint won 30
and the RL checkpoint won 34. There were five improved outcomes, one regression
(Diana), and 74 unchanged outcomes. Four incomplete pairs were excluded.
This modest descriptive gain does not establish a reliable strength improvement;
no checkpoint was deployed. Independent teacher top-choice agreement was 72.9%.
The imitation collection had one unavailable teacher action, excluded from learning.

Four of the six incomplete games showed repeating invalid-attack transitions in
Merlin/Fire Lorraine matchups. A Guo Jia/Polkhawk game also repeated transitions;
a Tristan/Ciel game reached the limit while changing state. Investigate these
replays before increasing training volume. Optional-response warnings involving
Backup Charger, Silvershine, Chalice of Blood, and ingredients are coverage
inspection candidates, not proof that a useful action was missed.

Deck-level results, exact replay identifiers, and follow-up priorities are in
`GrandArchiveSim/docs/rl-evaluation-20261030.json`. Checkpoints, raw reports, logs,
replays, frozen teacher, and deck inputs are preserved locally under the ignored
`DevTools/rl/artifacts/ga-linear-full-cycle-20261030/` directory.

### Tactical observation follow-up (2026-10-30)

The linear observation now includes each champion's distant status and public
field totals for distant cards, weapons, loaded weapons, and durability. Field
action descriptors include the same facts for the selected object. Effective
card types and the engine's functional-weapon load predicate are used; attached
card identities are not serialized. These are descriptive features, not a claim
that every loaded weapon is currently legal to attack with.

Existing linear-v1 checkpoints remain loadable: new weights start implicitly at
zero. Retrain and compare under the same engine and frozen opponent before
claiming an improvement; scores from the earlier observation are not a controlled
comparison. Unit coverage includes hidden-zone sentinels, both seat perspectives,
loaded/empty weapons, durability, and removed objects.

The subsequent full tactical-feature cycle scheduled 294 games and completed 291.
Across 82 mutually completed evaluation pairs, imitation and RL each won 30:
four improvements, four regressions, and 74 unchanged outcomes. No checkpoint
was promoted. Teacher top-choice agreement rose from 70.7% to 72.1%, which did
not translate into a net win gain. One unavailable imitation-teacher choice was
excluded from learning. The evaluation used the same fixed engine, frozen
opponents, and observation for both checkpoints; it does not isolate the causal
benefit of the new tactical features.

Two progressing evaluation games hit the step limit (Tristan and Mordred).
One Polkhawk training game exhausted its actions at the Unique-rule sacrifice
prompt for duplicate Misty the Joker cards; it was excluded from training.
Inspect that prompt and the Diana/Nico/Alice/Fire Lorraine regressions before
increasing training volume. Full artifacts are preserved under
`DevTools/rl/artifacts/ga-tactical-full-cycle-20261030/`; the tracked evaluation
JSON contains the compact results. Comparison reports now include
`pairedBeforeWins` and `pairedAfterWins`, excluding wins from incomplete pairs;
the existing `beforeWins`/`afterWins` fields retain their all-completed totals.

Evaluation reproducibility correction (2026-10-30): GA setup now resets the acting
perspective before shuffling. Previously a long-running worker could inherit the
last acting seat from its previous game, producing different starting deck orders
for the same seed. Seven of the 84 tactical-cycle before/after pairs had different
starting states; the earlier aggregate paired results must not be treated as a
controlled strength comparison. The saved evaluation audit records those pairs.
Tournament reports now include each game's `initialGamestateHash`; comparisons
reject unequal or missing hashes. Old reports require their saved replay hashes
before comparison, and mismatched starts require a fresh evaluation. Run
`php DevTools/rl/test_ga_start_reproducibility.php` to check startup isolation.

The fixed-start evaluation on fresh seed set `8000001` completed all 84 games
(42 per frozen checkpoint), covering all 21 learner archetypes in both seats.
All 42 paired initial-state hashes matched and neither policy changed. The
imitation checkpoint won 18 games; the RL checkpoint won 19. Rai — Ravishing
Mill in seat 1 improved from a loss to a win, with 41 unchanged outcomes and no
regressions. This small descriptive gain does not establish stronger play or
justify promotion. Artifacts are in
`DevTools/rl/artifacts/ga-start-fixed-evaluation-20261030/`, with compact results
under `fixedStartEvaluation` in the tracked evaluation JSON. Both checkpoints
were trained before the startup fix; evaluation used the corrected startup and
Unique-rule handling. Next, confirm on a second unused seed set with the same
frozen models and opponent before adjusting training.

Parallel evaluator regression checks:

```bash
python3 DevTools/rl/test_evaluate_ga_parallel.py
python3 DevTools/rl/test_compare_ga_evaluations.py
```

These check serial/parallel action equality, matching starts, frozen policies,
cache reuse/invalidation/corruption recovery, and both screening outcomes.

A four-full-game smoke benchmark on 2026-10-30 completed all games with identical
serial/parallel results: 16.05 s serial, 12.36 s with two workers, and 0.39 s
from cache. This is a small local measurement, not a promised speedup for every
matchup. Artifacts: `DevTools/rl/artifacts/ga-parallel-benchmark-20261030/`.

The first screened confirmation run (`ga-screened-confirmation-20261030`) finished
in 119.5 seconds using two workers. All preflight checks passed. On fresh screen
seed `9100001`, 11 completed pairs had identical outcomes (5 wins each). The
imitation baseline's Ciel versus Tristan game reached 1,000 steps while the
candidate lost that same starting position in 237 steps. The incomplete pair
was excluded, and the screen stopped the larger 168-game evaluation. This is
not a demonstrated outcome regression or evidence of improvement; no model was
promoted. Diagnose that matchup at an equal higher step limit for both policies
before further validation. Compact results are under `screenedConfirmation` in
the tracked evaluation JSON.

Ciel timeout diagnosis (`ga-ciel-timeout-diagnostic-20261030`): reran episodes
9–10 with both frozen policies at an equal 2,000-step limit. Both starting hashes
and every original replay action prefix matched. The imitation baseline won
episode 9 at step 1,302; the RL candidate lost at step 237. Episode 10 remained
an unchanged loss for both. All four games completed and neither policy changed.
This confirms a screening regression rather than an indefinitely stuck game.
The candidate remains unpromoted; full validation seeds remain unused. Inspect
the differing Ciel decisions before another training experiment. See
`cielTimeoutDiagnostic` in the tracked evaluation JSON.

### Frozen shared foundation with archetype adjustments

Use `--archetype-adapters` with `train_ga_tournament.php --checkpoint <shared-model>`
or `run_ga_learning_cycle.py --teacher-checkpoint <shared-model>` to train separate
linear adjustments while freezing the shared `__linear__` weights. This mode
requires GA `linear-v1`. Start from the imitation checkpoint rather than a rejected
shared RL candidate. A saved adapter checkpoint automatically restores this mode
for continued training and evaluation; existing shared checkpoints retain their
original behavior.

Routing uses the learner deck's explicit `archetypeId` in the tournament config,
or its full descriptive `name` when absent. Keep this identifier stable across
training and evaluation. Give different strategies separate IDs even when they
share a champion; intentionally reuse an ID only for decks that should share
adjustments. The routing never examines the opponent's deck or either draw order.
Unseen archetypes use the unchanged shared foundation. Missing routing fails
training rather than silently modifying shared weights.

Checkpoints store `ga_archetype_adapters: frozen-base-v1` and sparse
`__archetype__:<id>` adjustment buckets alongside the original shared weights.
Inference sums the foundation and the selected adjustment. Only the active
archetype's bucket accepts updates; incomplete games remain excluded. Comparisons
now include `byArchetype`, including incomplete pairs, so aggregate wins cannot
hide deck-specific regressions. A ten-game pilot touches only the archetypes in
those scheduled games; use at least a 42-game cycle to reach all 21 archetypes.
This is local experimentation and does not change the deployed heuristic bots.

The first adapter pilot (`ga-archetype-adapter-pilot-20261030`) completed ten
training games across five archetypes, preserving the shared weights exactly.
The 12-game screen at 2,000 steps completed every pair: six baseline wins versus
five candidate wins, with one Ciel regression and eleven unchanged outcomes.
Untrained Tristan's replay actions were identical in both seats. Screening took
the full cycle to 155 seconds and rejected the candidate before final validation.
The reused screening seeds are diagnostic; this is not independent strength
validation. Isolation works, but Ciel's own update can still degrade its play.
No model was promoted. Detailed results: `archetypeAdapterPilot` in the tracked
evaluation JSON.

### Parallel training rounds for every archetype

`train_ga_archetypes.py` schedules one isolated job per distinct archetype ID in
the config, with two concurrent PHP workers by default. Within a job, learning
remains sequential. Every job starts from the same frozen incumbent checkpoint
and fixed heuristic opponent implementation. The filtered round-robin schedule
rotates opponents and alternates learner seats. Each archetype gets ten training
games followed by twelve matched before/after screening games by default.
For 21 archetypes that is 210 training games plus 504 screening games.

```bash
python3 DevTools/rl/train_ga_archetypes.py \
  --config DevTools/rl/artifacts/ga-tactical-full-cycle-20261030/inputs/config.json \
  --baseline DevTools/rl/artifacts/ga-tactical-full-cycle-20261030/inputs/baseline \
  --checkpoint DevTools/rl/artifacts/ga-tactical-full-cycle-20261030/imitation/latest.json \
  --output DevTools/rl/artifacts/ga-archetype-round-1 \
  --workers 2 --training-games 10 --screen-games 12 --max-steps 2000 \
  --training-seed 11000001 --screen-seed 12000001
```

Repeat the same command with `--resume` to reuse completed, checksummed stages.
Worker count may change when resuming; config, decks, checkpoint, source code,
PHP version, seeds, game counts, and limits must match. Interrupted stage folders
are retained under `.interrupted-*` and that stage restarts from its original
checkpoint. Corrupted completed artifacts fail closed. The round directory is
locked against concurrent runners. `summary.json` updates as archetypes finish;
individual folders retain training, both evaluations, replays, logs and decisions.

A worker is eligible for merging only if all its training and evaluation games
complete, screening shows more paired wins, and no paired outcome regresses.
Ties keep the incumbent. Screening is deliberately conservative and is not a
statistical strength claim. Failed workers prevent publication of a new merged
candidate; successful stages remain resumable. Rejected archetypes retain their
previous adjustment, and untrained archetypes retain the shared foundation.

The merger verifies that each candidate changed only its assigned adjustment
bucket. Shared weights, other archetypes, and policy settings must be identical
to the incumbent. It produces `candidate.json` locally; it does not deploy or
promote it. Validate accepted adjustments on separate, unused seeds before use.
Later rounds can take that checkpoint, advance `--start` by the previous training
game count to rotate through further opponents, and use fresh training/screening
seed ranges. With 21 distinct decks, 40 games per archetype cover all 20 opponents
in both seats; a ten-game batch covers five opponents, not the full field.

Run scheduler, merge, resume, corruption, and serial/parallel equivalence checks:

```bash
python3 DevTools/rl/test_train_ga_archetypes.py
```

The first full round (`ga-archetype-round-1`) finished all 714 games in about
20 minutes with two workers. Five adjustments passed the 12-pair screen:
Astra Arisanna, Nico, Polkhawk, Tristan, and Water Diao Chan. The other sixteen
retain the incumbent. These are screening results, not demonstrated strength
gains; nothing was deployed. Independent validation uses 40 matched games per
accepted archetype (every opponent in both seats), fresh seed 13000001, and the
same 2,000-step limit: 400 actual games including before and after. Round results
are preserved under `parallelArchetypeRound1` in the tracked evaluation JSON.

### Replay review and decision-position bank

New GA learner replays retain policy observations, available semantic action keys,
filtered legal indices, and conservative `decisionSupport` diagnostics. This does
not change training rewards or label losses as bad actions. Optional YESNO and
MZMAYCHOOSE scoring gaps are identified through the existing scorer; truncated
catalogs are separate gaps. Other decision types remain **unknown**, rather than
being assumed supported. A heuristic gap does not prove missing engine ability
code. Fixed opponents are not classified by the learner's current support code.

```bash
python3 DevTools/rl/ga_decision_bank.py path/to/before path/to/after \
  --bank path/to/bank.json --output path/to/comparison.json
python3 DevTools/rl/test_ga_decision_bank.py
```

The comparator requires matching frozen evaluation conditions and initial states,
then identifies the first concrete action divergence. Later actions are not treated
as equivalent positions after the games diverge. The first divergence is a review
lead, not proof of causation. Legacy replays can be compared but cannot supply
missing observations or support diagnostics.

The reusable bank deduplicates archetype + observation + available action keys,
retains chosen-action examples and checksummed replay provenance, and separates
`missing_support`, `decision_review`, and `unknown_support`. Entries remain
`unreviewed`; a supported decision is not automatically a poor decision. Its
policy-input projection excludes full gamestates and opponent hidden cards.
This is a feature-position bank for review and future decision tests, not a
restorable engine snapshot or counterfactual rollout evaluator. Replay paths
refer to local diagnostic files that contain full engine state and must not be
fed to the policy. All imported evaluation examples are review-only; retire their
seeds from holdout before deliberately using them for training.

The initial focused diagnostic cohort is Tristan — Shadowstrike, Fire Lorraine —
Red Hare, and Water Guo Jia — Genbu. Keep the full opponent roster; selecting these
learners does not mean training only against each other.

The 12-game focused capture pilot completed all games and collected 1,233 distinct
positions: 605 with optional-scoring gaps, 122 supported optional decisions, and
506 unclassified decisions. These count positions, not cards. Lorraine and Guo
Jia had identical before/after action sequences; Tristan diverged in both games.
The bank keys also include the evaluated source identity, preventing support
labels from being silently reused across engine changes. See `decisionBankPilot`
in the tracked evaluation JSON. No training update or deployment occurred.

Rank repeated support gaps without counting both halves of paired evaluation as
independent games:

```bash
python3 DevTools/rl/triage_ga_bank.py path/to/bank.json --output path/to/triage.json
python3 DevTools/rl/test_triage_ga_bank.py
```

The first focused review is in
`GrandArchiveSim/docs/rl-focused-triage-20261030.md`. It separates concrete support
work from unproven action-quality hypotheses and records which seeds were reviewed.


### Evaluate heuristic changes directly

Use the tournament runner with `--evaluate --learner-heuristic current` to
exercise the current heuristic rather than the checkpoint's learned choices.
Use `--learner-heuristic frozen` for the baseline chooser. Both require a
checkpoint for the existing replay representation, leave its weights unchanged,
and keep the same frozen opponent. Match config, seed, start, and game count,
then compare reports and import their replays with the decision-bank tool.
Reports identify the chooser explicitly. Frozen learner support is left unknown
rather than classified using current scoring code. A historical baseline can
include multiple behavioral differences; its results cannot isolate one patch.

### Compare alternatives at a saved decision

`compare_ga_position.py` rebuilds a replay prefix and runs each enumerated action
(and any additional heuristic answer) in a separate PHP process. New captures
include `initialSnapshotHash` for the exact saved snapshot; older captures are
rejected. Each recorded learner observation and acting seat is checked while
replaying the prefix. Branches must reconstruct the same complete state hash,
public observation, and action list or the comparison fails.

```bash
python3 DevTools/rl/compare_ga_position.py path/to/game-1.json \
  --step 93 --horizon 300 --seed 19000001 --output /tmp/position-comparison.json
```

Steps are one-based. The selected step must have a recorded linear public
observation. Continuations use current heuristics for both seats; this does not
reproduce a historical learned or frozen continuation. The engine restores its
private state, but the chooser receives only the existing legal-action/public
policy interface. Reports omit the full private state. Each branch gets the
same continuation RNG seed, but different random calls can still yield different
later draws. Prefix random behavior that changes recorded public evidence or
branch state hashes causes rejection rather than silent alignment.

Treat this as a review tool for one saved hidden-state realization, not a causal
quality label or training target. An unfinished branch is not a loss. It is not
exhaustive for large synthesized multi-choice spaces. Scratch games are uniquely
named and cleaned up, including on validation failure. Tests:
`python3 -m unittest discover -s DevTools/rl -p test_compare_ga_position.py`.

Use `--seeds 19000001 19000002 19000003` instead of `--seed` to repeat
each alternative across distinct continuation seeds. The report retains every
run and summarizes wins, losses, draws, unfinished branches, and action counts
from the deciding player's perspective. It verifies the same reconstructed
position across seeds. Repeated continuations share one saved position and must
not be counted as independent full-game samples or automatic training labels.

Shortlist useful decisions before running continuations:

```bash
python3 DevTools/rl/select_ga_positions.py path/to/replay-directory \
  --handlers KindleProcess RedHareDiscard ek7r2d7uz4:0:CardActivated \
  --limit 10 --output /tmp/pressure-shortlist.json
```

The shortlist ranks recorded low-life/lethal-pressure decisions, declined
optional actions, and decisions with several distinct alternatives. It reads
recorded player observations and public graveyard annotations only; private
game state is not parsed. Identical replay files are deduplicated and older
captures without snapshot checksums are excluded. Ranking is a review aid, not
a preferred-action label. Adjacent choices can belong to the same sequence.

Position-comparison reports include `position.choices` alongside learned
`actionKeys`. Use the executable choice's zone reference to distinguish separate
copies of the same card: semantic keys can coincide even when the targets and
outcomes differ. Branch reconstruction checks both representations. Multi-seed
summaries include the executable choice as `action`; these review fields do not
change policy inputs or expose hidden zone contents.

### Versioned heuristic opponents

Keep the historical reference for long-term comparisons and add newer snapshots
as candidate opponents. A snapshot freezes the four chooser files, not the shared
engine or legal enumeration. Never overwrite a version; snapshots carry source
hashes and candidate metadata, and evaluation verifies their content.

```bash
python3 DevTools/rl/ga_opponent_pool.py snapshot \
  --output DevTools/rl/artifacts/ga-opponent-versions/current-20261030 \
  --label current-20261030
```

A pool JSON uses paths relative to that JSON file:

```json
{"opponents":[
  {"id":"historical","role":"reference","path":"../ga-tactical-full-cycle-20261030/inputs/baseline"},
  {"id":"current-20261030","role":"candidate","path":"current-20261030"}
]}
```

The local `artifacts/ga-opponent-versions/pool.json` now contains these two versions.
To evaluate the current heuristic against both:

```bash
python3 DevTools/rl/ga_opponent_pool.py evaluate \
  --pool DevTools/rl/artifacts/ga-opponent-versions/pool.json \
  --config DevTools/rl/artifacts/ga-tactical-full-cycle-20261030/inputs/config.json \
  --checkpoint DevTools/rl/artifacts/ga-tactical-full-cycle-20261030/imitation/latest.json \
  --output DevTools/rl/artifacts/ga-opponent-pool-full-20261030 \
  --archetype 'Tristan — Shadowstrike' \
  --archetype 'Fire Lorraine — Red Hare' \
  --archetype 'Water Guo Jia — Genbu' \
  --games 40 --seed 25000001 --workers 2
```

Games are **per archetype per opponent version**: this command runs 240 games.
Forty games covers each of the other twenty configured decks from both seats.
Every version receives the same schedule/seeds. Reports separate wins and completed
games by version and archetype; capped games are not counted as wins. Each result
contains copies of all opponent snapshots, detailed replays and input hashes.
Output directories cannot be reused. The checkpoint is required by the underlying
runner but the learner uses the current heuristic, not learned actions.

This is evaluation only. It does not update training opponents, a live bot, or the
reference automatically. Promote a candidate only after decision checks, fresh
balanced evaluation and review of regressions; retain the old snapshot and existing
reports. Pool role labels are descriptive, not an automated quality gate.
Local snapshots/results are ignored artifacts: archive them with experiment data
if they must survive a clean checkout. Do not edit runtime sources during a run.

### Rotating improvement batches

`ga_archetype_rotation.py` pins a batch's deck files, checkpoint, opponent pool,
and starting chooser. Keep a `reference` opponent in every pool. Select three
archetypes to improve, then run **before** evaluation before changing policies.
Both evaluation phases cover the entire configured roster against every version,
with every opposing deck in both seats, on separate screen and validation seeds.
For 21 decks and two versions this costs 3,360 games per phase; this is a broad
acceptance check, not the fast loop for each individual decision fix.

```bash
python3 DevTools/rl/ga_archetype_rotation.py start \
  --output DevTools/rl/artifacts/ga-rotation-alice-dante-polkhawk-20261030 \
  --pool DevTools/rl/artifacts/ga-opponent-versions/pool.json \
  --config DevTools/rl/artifacts/ga-tactical-full-cycle-20261030/inputs/config.json \
  --checkpoint DevTools/rl/artifacts/ga-tactical-full-cycle-20261030/imitation/latest.json \
  --archetype 'Alice — Ravishing Mill' \
  --archetype 'Dante — Water Unique Allies / Ravishing Mill / Suited' \
  --archetype 'Polkhawk — Suited' --seed 26000001 --validation-seed 27000001

python3 DevTools/rl/ga_archetype_rotation.py evaluate \
  --batch DevTools/rl/artifacts/ga-rotation-alice-dante-polkhawk-20261030 \
  --phase before --workers 2
# Improve decisions, add decision-position checks, then run --phase after.
python3 DevTools/rl/ga_archetype_rotation.py review \
  --batch DevTools/rl/artifacts/ga-rotation-alice-dante-polkhawk-20261030
```

The review rejects mismatched schedules, starting states and mutated checkpoints.
Incomplete or regressed paired games anywhere in the roster block eligibility;
each target must gain wins in both seed sets. Eligibility is only a request for
human decision review, not statistical proof or automatic promotion. Preserve
missing-ability findings separately from poor-choice findings in the decision bank.

After reviewing the evidence, create a **new** pool file retaining the historical
reference and adding the batch's `after/chooser` snapshot, then start the next
batch using that pool. Record accepted archetypes and review rationale alongside
it. Do not edit an existing batch's pool. Snapshots represent the shared chooser,
not independent per-champion policies; the full-roster screen guards that shared
behavior. Rotation and promotion remain explicit commands. Interrupted evaluation
outputs are retained for diagnosis; start a fresh batch rather than overwriting
results. Batch artifacts are local and must be archived for long-term retention.
