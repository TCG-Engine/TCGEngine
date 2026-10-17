# Reproducible balance simulations

## Pro bot evaluation

```sh
php DevTools/DFGSim/pro-bot-tests.php
php DevTools/DFGSim/pro-evaluation.php --out=DevTools/DFGSim/results/pro-current
php DevTools/DFGSim/verify-pro-evaluation.php DevTools/DFGSim/results/pro-current
php DevTools/DFGSim/pro-report.php DevTools/DFGSim/results/pro-current
php DevTools/DFGSim/pro-evaluation.php --suite=all --out=DevTools/DFGSim/results/pro-all
php DevTools/DFGSim/balance.php --profiles=pro --repeats=1 --ablation-cards=0 --out=DevTools/DFGSim/results/pro-selfplay
```

The default head-to-head suite covers six identical-color openings, both Pro seats, and
both initial reaction priorities (24 games). The full suite covers all ordered color pairs
(144 games). Seeds are paired across seat assignments; initial openings are fixed before
play. Capped games remain separate. Raw `matches.jsonl` records every authoritative action;
`summary.json` records outcomes, timings, code/data hashes and main selections divided by
opportunities where the card was available. Selection rates remain policy-dependent.
See the live simulator README for the search horizon and pruning limitations.

The [Pro loss-analysis report](results/pro-improvement/report.md) compares four development
variants on the original scenarios, then the frozen selected candidate against the original
on fresh seeds across all ordered openings. Direct crossplay uses `--opponent=pro-v1` and
an additional seed offset. `--runtime-root` selects preserved runtime snapshots; `--repeats`
and `--seed-offset` control reproducible scenario sets. `--suite=balanced` uses one initial
priority per ordered opening, selected by index parity, and both Pro seats (72 games).
Run `verify-pro-evaluation.php <folder>` on every cohort before
`python DevTools/DFGSim/pro-improvement-report.py` to regenerate the report.

The [follow-up crossplay study](results/pro-crossplay/report.md) preserves negative results
as well as candidate improvements. `pro-reaction-audit.php` enumerates revealed endgame
reaction sequences; `pro-plan-audit.php` inspects public-state payoff matrices and dominance.
Both accept a cohort directory and matching runtime directory as positional arguments.
`pro-solver-tests.php <runtime>` checks the experimental solver contract.

For parallel experiments, use `pro-evaluation.php --shards=4 --shard=0` through `--shard=3`,
each with a separate output directory. Merge all parts with
`php DevTools/DFGSim/merge-pro-evaluations.php <out> <part0> <part1> <part2> <part3>` and
replay-verify the merged output. Seeds and scenario coverage match the unsharded run.
`--opponent=reference --reference-root=<snapshot>` selects another frozen Pro implementation;
its engine, catalog compiler, and card data must match the selected runtime. The historical
`tactical` keys in that analysis output denote the reference seat; opponentProfile/referenceHash
record its identity. `python DevTools/DFGSim/pro-crossplay-report.py` regenerates the study.

## Heavy Feint v43 study

The [v43 report](results/heavy-feint-v43/report.md) compares Heavy Feint against the matched
v42 baseline: 2,160 games per version, seed offset 19,000,000, ten repeats, three profiles,
both priorities, and all ordered openings. Only Heavy Feint's advantage-spending read changes.
The no-token matrix is unchanged; a separate 396-exchange matrix per version tests spending.

```sh
php DevTools/DFGSim/verify-balance.php DevTools/DFGSim/results/heavy-feint-v43/simulation DevTools/DFGSim/results/heavy-feint-v43/runtime
php DevTools/DFGSim/verify-pair-analysis.php DevTools/DFGSim/results/heavy-feint-v43/pairs DevTools/DFGSim/results/heavy-feint-v43/runtime
php DevTools/DFGSim/heavy-feint-spend-analysis.php DevTools/DFGSim/results/iron-guard-v42/runtime DevTools/DFGSim/results/heavy-feint-v43/prior-spend-pairs.json
php DevTools/DFGSim/heavy-feint-spend-analysis.php DevTools/DFGSim/results/heavy-feint-v43/runtime DevTools/DFGSim/results/heavy-feint-v43/current-spend-pairs.json
php DevTools/DFGSim/heavy-feint-metrics.php DevTools/DFGSim/results/iron-guard-v42/runtime DevTools/DFGSim/results/iron-guard-v42/simulation/baseline.jsonl DevTools/DFGSim/results/heavy-feint-v43/v42-metrics.json
php DevTools/DFGSim/heavy-feint-metrics.php DevTools/DFGSim/results/heavy-feint-v43/runtime DevTools/DFGSim/results/heavy-feint-v43/simulation/baseline.jsonl DevTools/DFGSim/results/heavy-feint-v43/v43-metrics.json
python DevTools/DFGSim/heavy-feint-report.py
```

The v43 generation command uses `balance.php --repeats=10 --max-rounds=60 --ablation-cards=0
--seed-offset=19000000 --runtime-root=DevTools/DFGSim/results/heavy-feint-v43/runtime
--out=DevTools/DFGSim/results/heavy-feint-v43/simulation` (one shell command).
These are bot-policy associations and fixed-plan damage measurements, not causal human win rates.

## Iron Guard v42 study

The [v42 comparison](results/iron-guard-v42/report.md) compares the original Iron Guard,
v41 carried block, and v42 conservative attack pressure. Each trial has 2,160 matched games
and an independently replay-verified 40,401-exchange pair matrix. The
[v41 study](results/iron-guard-v41/report.md) retains the isolated original-versus-block-carry analysis.
Snapshots and raw traces are stored with each study; the live game now uses v42.

```sh
php DevTools/DFGSim/verify-balance.php DevTools/DFGSim/results/iron-guard-v42/simulation DevTools/DFGSim/results/iron-guard-v42/runtime
php DevTools/DFGSim/verify-pair-analysis.php DevTools/DFGSim/results/iron-guard-v42/pairs DevTools/DFGSim/results/iron-guard-v42/runtime
python DevTools/DFGSim/iron-guard-analysis.py
python DevTools/DFGSim/iron-guard-revision-report.py
```

`balance.php --runtime-root=<snapshot>` selects an isolated runtime for reproducible trials.
The v42 batch uses `--repeats=10 --max-rounds=60 --ablation-cards=0 --seed-offset=19000000`.
Main-play win associations are selected-use statistics; compare profile exposure and preassigned
opening outcomes before interpreting changes in pooled win rate.

## Historical v37 play-to-win batch

```sh
php DevTools/DFGSim/balance.php --repeats=10 --ablation-cards=0 --seed-offset=17000000 --out=DevTools/DFGSim/results/play-win-v37
php DevTools/DFGSim/verify-balance.php DevTools/DFGSim/results/play-win-v37
php DevTools/DFGSim/play-win-report.php DevTools/DFGSim/results/play-win-v37 100
php DevTools/DFGSim/pair-play-win-report.php DevTools/DFGSim/results/play-win-v37 100
```

This runs 2,160 games: all 36 ordered opening pairs, both initial reaction priorities, ten seeds,
and tactical/casual/explore profiles. Read `play-win-report.md` for actual wins divided by completed
player-games when a card was used, with draws in the denominator and repeated uses deduplicated.
The headline tables require 100 completed player-games per card/role. `play-win-rates.csv` includes
all cards, roles, and profile strata, including smaller samples. `balance-report.md` retains its
separate draw-adjusted score metric. These are associations under the bots' policies, not causal
estimates. This batch does not run card-removal experiments.
`pair-play-win-report.md` ranks exact main-plus-bonus combinations from these same games;
it counts each pair once per player-game, pools token choices, and reports profile-specific
exposure. Its win rates are full-game associations and differ from the one-round pair matrix.
V37 uses the same scenarios after restoring Haymaker from 8/0 to 7/0. Its next-round
-2 block penalty on zero combat damage remains. No other card or bot behavior changes.
V36 used the same scenarios with Darting Strike 6/0 and its optional Yellow cancel before
reactions. Both players choose replacements without seeing the other pending choice. Trace
`plans` retain initial commitments, `cancels` records decisions, and `combatPlans` holds the
resolved replacements; damage uses combatPlans. Main/pair attribution remains on the initial
commitment, so Darting gets credit for its escape. Replacement mains cannot spend tokens.
`cancel-tests.php` and `cancel-analysis.php` cover legality and exhaustive replacement outcomes.
V35 used the same scenarios after adding Brace pressure suppression, Mirror Step exact printed-3
reads and held-token removal, Clinch Throw next-round burn lock, and Haymaker 8/0 with a
next-round recovery penalty on a miss. Darting Strike remains unchanged. Its 81-statline study is
`results/pairs-v35/darting-analysis.md`; candidate results do not implement or price a cancel.
The v17/v18/v19/v20/v22/v28/v29/v30/v31/v32/v33/v34/v35/v36 batches remain historical. V34 used the same
starting scenarios after the three-card Red identity revision: Quick Jab 3/2 with +2 attack
without a bonus; Feint 2/3 with +2 attack and 3 penetration against printed block 4+; Combination
Strike 3/1 with any one token granting +1 attack, or any two instead granting +4 attack,
+2 block, and 2 penetration. Its old color-specific payoffs and extra destination are removed.
`combination-analysis.php` additionally resolves every legal bonus/opponent/token-color
combination through the authoritative engine (22,110 resolutions), reporting each spend mode
separately. The normal pair matrix still starts without tokens.
V33 used the same starting
scenarios after raising Outside Step from 1/3 to 1/4. Its +6 attack read and independent on-hit
pressure remain unchanged. Printed block 4 also activates opposing strong-guard reads.
V32 used the same starting
scenarios after lowering Guarded Strike from 0/5 to 0/4, keeping retaliation and its Green
objective. V31 used the same starting scenarios
after making Guarded Strike 0/5 with 2 direct damage after taking no combat damage. The clean-counter
Green objective remains. Combat damage and direct damage are recorded separately, with their sum
used for damage-based objectives, other triggers, and defeat. Both retaliations check combat
damage before resolving simultaneously, so the mirror deals two each and awards neither Green.
V30 used the same seeds and starting
scenarios after lowering Rooted Guard from 0/7 to 0/6; its +3 attack read and delayed Green
objective remain unchanged. V29 used the same seeds and starting scenarios
after raising Driving Tackle's printed block from 0 to 1. V28 included the preceding revisions,
including these edits since v22: Outside Step 1/3 with +6 attack on its read and independent
on-hit +1 attack next round; Rooted Guard 0/7 with a +3 attack read and delayed Green objective;
Driving Kick +4 conditional block; Counterpunch 4/2 with +3 conditional block; Find the Gap 4/2.
The live engine and bot evaluate next-round pressure and public delayed objectives. The
one-round pair matrix measures immediate outcomes only and cannot price those future rewards.
Changes since v22 are bundled, so comparisons do not isolate any single card edit.
V22 used the same seeds and starting scenarios after
raising Catch the Opening's block to 2 and changing Outside Step to 3/2 with +4 attack against
printed block 4+. Both edits are included, so comparisons with v20 cannot isolate either change.
V20 changed Darting Strike to 4/2 with +3 block against printed attack 6+. V19 introduced the Yellow update,
replacing three Yellow cards with Outside Step, Glancing Step, and Driving Kick. The bot now
values exact-one-damage pressure/objectives and chooses forced opposing destinations. Bot-choice
cohorts and later game paths can change, so cross-version rate changes remain descriptive.

Current traces record Mirror Step token-removal choices before objective awards, then route cuts
and forced opposing destinations before hand switches. The replay verifier checks those
actions, recovery after damage, and main-card burns as well as damage/token accounting,
including a removed color being earned again from a simultaneous objective.
Explore chooses token removals, route cuts, and forced destinations uniformly; tactical/casual bots use their public-information heuristic.

Run bot-vs-bot matches with the current local card snapshot and rules. The live simulator, bot,
hosted editor, and card balance are not modified. Matches are in-memory; no browser sessions or
application database are used.

```sh
php DevTools/DFGSim/balance.php --repeats=4 --ablation-cards=6 --ablation-repeats=1 --out=DevTools/DFGSim/results/balance-v2
php DevTools/DFGSim/verify-balance.php DevTools/DFGSim/results/balance-v2
```

The default baseline runs all 36 ordered opening-hand pairs, both initial reaction-priority
settings, four seed repetitions, and three symmetric bot profiles: tactical, casual, and explore.
That is 864 baseline matches. Explore samples a uniform main card and next hand, commits a bonus
with 25% probability, and uses the existing reaction heuristic. It helps expose cards the live
heuristic might rarely choose. Separate card outcomes per profile are retained in `summary.json`.

Candidate removals are selected from main, bonus, and reaction win associations. A reference batch
uses fresh seeds and tactical bots; each candidate is removed from seat 0's entire available card
pool and the exact starting colors, seed streams, and initial priority are replayed. These are
paired experiments: score with the card minus score without it, with approximate paired-bootstrap
intervals. The removed card is unavailable as a main, bonus, or reaction. The bot is allowed to
adapt. This tests that bot's reliance on a card, not the isolated printed effect or universal strength.

Confirm candidates in a larger independent batch:

```sh
php DevTools/DFGSim/balance.php --repeats=1 --candidate-ids=35,21,30,12 --ablation-repeats=4 --seed-offset=15000000 --out=DevTools/DFGSim/results/confirmation-v2
php DevTools/DFGSim/verify-balance.php DevTools/DFGSim/results/confirmation-v2
```

Options:

- `--repeats`: baseline repetitions per ordered opening, priority, and profile.
- `--profiles`: comma-separated `tactical,casual,explore` (all by default).
- `--max-rounds`: diagnostic cap, 60 by default. Capped matches have no assigned winner.
- `--ablation-cards`: number of automatically selected candidates; zero skips removals.
- `--candidate-ids`: explicit candidate IDs, replacing automatic selection.
- `--ablation-repeats`: reference repetitions per opening/priority, one by default.
- `--seed-offset`: use disjoint seeds for confirmation.
- `--out`: output directory. Re-running overwrites that directory's experiment files.

Output: `balance-report.md`, `summary.json`, `baseline.jsonl`, and `ablation.jsonl`. JSONL traces include
complete resolved rounds and both players' plans, burns, damage, life, and seed metadata. Card
outcome samples deduplicate repeated plays within a player-game; event damage statistics retain
every play. Draws score 0.5, wins 1, losses 0. Capped games are counted separately and omitted from
score denominators. Removal pairs are scored only when both outcomes finish.

`verify-balance.php` reconstructs recorded damage, resource usage, and sample denominators, then
checks paired experiments against their raw outcomes and the current catalog hash. Reports record
the rules version and catalog hash, so results can be compared against later balance revisions.

Treat score-when-used as association. A low-cost attack reaction may appear to win almost every
time because the bot only burns it to secure lethal damage. Removal experiments are a stronger
lead, but remain specific to this bot. Small samples, correlated matchups, multiple comparisons,
and censored stalled matches need to be considered before nerfing a card.

Rules version 4 adds advantage tokens. JSONL rounds now include `advantagesBefore`,
`advantagesAfter`, committed spending, and actual hand switches. Summaries count earned/spent
tokens per color and two-color Combination Strike spends. Explore also chooses a uniform legal
spending variant; tactical/casual bots value objectives and token payoffs. These policies changed,
so historical results are not a controlled comparison of advantage alone. The verifier replays
commitments, reaction windows, objective awards, and switches through the authoritative engine,
including card-removal traces. It fails immediately when a report's catalog hash differs.

The first implementation smoke run is in `results/advantage-v4-smoke`: 216 matches across the
three profiles and all ordered openings/priority settings. All colors were earned and spent,
but no two-color bot combo was observed. The combo itself is covered by domain and client tests;
this run verifies mechanics, not card balance. Historical results remain in their original folders.

`results/switching-v5` repeats the 216-match three-profile smoke run after making own-color
destinations rare. Only cards 2, 11, and 14 retain a printed stay option, and Yellow's extra
destination cannot be the active hand. The run completed 174 decisive matches and 42 draws,
with no 60-round caps (v4 had two). Recorded same-hand choices fell from 2,520/6,518 (38.7%)
in v4 to 222/6,410 (3.5%). Both count actual end-of-round selections; finished rounds without
a switch are excluded. All 41,725 recorded-data checks pass. This demonstrates reduced staying
in these bots, not elimination of all possible cycles or a settled balance result.

`results/advantage-v4-combination` also exercises the removal pipeline with Combination Strike:
72 tactical baseline matches plus 144 reference/removal matches. Authoritative replay verification
passes for the baseline and both sides of every removal pair. Its small exploratory effect estimate
is inconclusive; no balance change was made from this smoke test.
