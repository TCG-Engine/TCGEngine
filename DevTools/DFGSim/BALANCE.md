# Reproducible balance simulations

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
