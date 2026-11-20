# Focused bot decision review — 2026-10-30

Scope: Tristan — Shadowstrike, Fire Lorraine — Red Hare, Water Guo Jia — Genbu.
Evidence: `DevTools/rl/artifacts/ga-decision-bank-pilot` (12 completed games,
seed 14000001; two matched before/after games per learner). These seeds are now
review data, not fresh validation. No model was promoted.

## Priority queue

1. Tristan: Assassin's Ripper's Buff activation is the most frequent optional
   scoring gap (484 distinct positions across ready/rested variants).
2. Tristan: Sadi, Blood Harvester's Return activation (238 positions).
3. Tristan: Smoke Bombs (59 positions).
4. Guo Jia: review fast-action support for Cerulean Decree, Fracturize and
   Refracting Missile. Their availability is recorded, but the optional scorer
   does not supply a value in the encountered contexts.
5. Lorraine: inspect graveyard-choice handlers before assigning gaps to cards.
   Current semantic action keys retain graveyard zone indices, so an index alone
   must not be interpreted as a stable card identity.

Position counts overlap and repeated opportunities are correlated. The reusable
triage tool ranks by distinct paired games first, then distinct positions. Both
sides of a paired replay count as one game. This pilot has only two games per
learner, so these priorities indicate coverage opportunities, not population
frequency or causal importance. All gaps refer to heuristic scoring support;
they do not establish that engine card abilities are missing.

## Tristan divergence review

Game 1, step 65: incumbent plays Winbless Lookout; candidate plays Sadi, Blood
Harvester. Both receive the same observation and five legal choices. The
incumbent eventually wins and the candidate loses. The candidate selects the
higher-cost play (3 reserve versus 2), but no counterfactual continuation has
established that this decision causes the loss. Keep the position unlabelled.

Game 2, step 456: incumbent selects Dungeon Guide; candidate selects Liminal
Guide as the target. Both eventually lose. Their target power, HP and readiness
features are identical, though the card identities differ. This is a useful
feature/targeting review position, not evidence that either target is correct.

Neither divergence is an optional-scoring gap: both are currently classified
as unknown support. Do not train a preferred action from these results alone.

## Next implementation batch

Add narrowly scoped scoring support for Assassin's Ripper, Sadi Return and
Smoke Bombs with decision fixtures for useful activation and deliberate pass.
Then collect a new diagnostic batch and check whether the gaps disappear without
regressing those fixtures. Preserve the existing checkpoint while doing so.


## First support batch implemented

The heuristic now scores Assassin’s Ripper when its +2 crosses the current
weapon attack’s lethal threshold, Sadi’s return before lethal incoming combat
damage, and Smoke Bombs for a safe draw without granting an enemy-only ally
stealth. Smoke Bombs target selection prefers a friendly ally without stealth.
All activation decisions retain the engine’s cost/legality checks. Sadi’s
spell-response rescue and broader offensive Ripper timing remain future work.

Both-seat decision regressions cover activation, deliberate passes, unavailable
costs, post-damage timing, friendly targeting, and deck exhaustion. This improves
ability scoring coverage; it does not establish a win-rate improvement. Historical
banks retain their original source identity and classifications; generate a new
bank for the changed scorer rather than relabeling old observations.

## Direct heuristic screen

A fresh Tristan screen used seed 15000001, five opposing archetypes in both seats,
and ten games each for current and historical frozen heuristics against the same
frozen opponent. All 20 games completed. Both won 7/10; no paired outcome changed.
Six pairs first diverged on Smoke Bombs activation instead of passing. The other
four had identical action sequences. Ripper and Sadi return were not activated
in this sample, so no tournament conclusion is available for those abilities.
The comparison created 2,322 review positions. This historical baseline comparison
does not isolate the three-card patch. No model was trained or deployed.

Artifacts: `DevTools/rl/artifacts/ga-tristan-heuristic-screen-20261030/`.
Next, add decision-position scenarios that actually exercise Ripper and Sadi,
then tackle Guo Jia's fast-action gaps.

## Focused decision boundaries

Expanded both-seat runtime scenarios: Ripper preserves preparation when damage
is already lethal, cannot use a rested weapon, and does not boost a different
weapon's attack. Sadi stays when another unit is attacked or the incoming attack
is nonlethal. The decision suite passes 584 checks. These are synthetic decision
regressions, not full-game evidence of stronger play.

The position bank now includes support evidence in its identity so frozen
unknown-support observations cannot swallow current supported/missing-support
records with identical public inputs. Existing banks remain historical; rebuild
into a fresh bank to apply the new identity. All three bank/triage tests pass.

Guo Jia review confirms the next batch needs distinct policies: Cerulean Decree
has modal negation, power reduction and memory draw; Fracturize selects an item
or weapon; Refracting Missile selects a damage target. Their fast-action scoring
remains pending, including target legality and follow-up choices.

## Guo Jia response scoring: Missile and Fracturize

Refracting Missile now takes a fast-action window when its visible Fractal-scaled
damage can finish a legal opposing unit. Target scoring covers both declaration
and legacy resolution prompts, prioritizing champion lethal and useful ally
kills. It conserves the card for nonlethal chip opportunities. Departed Fractals
are excluded from the estimate.

Fracturize now values opposing weapons with positive power and selects those
over friendly equipment. Items and zero-power weapons are deliberately left
alone until their lost abilities can be valued against granting Reservable.
Both spells respect activation/payment checks, target protection and an already
pending friendly copy. This is conservative heuristic coverage, not a learned
policy update or proof of a win-rate improvement.

Both-seat runtime decision tests pass 614 checks; the RL policy suite passes
44 checks. Cerulean Decree remains an explicit scoring gap: its selected modes,
negation payment estimate and follow-up targets must be implemented together.
Next batch: that modal policy, then a fresh paired Guo Jia heuristic screen.

## Cerulean Decree and Guo Jia screen

Cerulean's declaration policy now chooses a -3 power response when it changes
lethal combat into survival, an opposing non-attack activation tax, or delayed
memory draw in a quiet opposing turn with spare cards. The tax is not treated
as guaranteed negation and never inspects opposing hand identities. Modal answers
handle two-mode and reduced-option prompts, and target choices decline friendly
or protected targets. The declared resolver's `ipl6gt7lh9` effect now shares the
power calculation of the legacy `ipl6gt7lh9-debuff` effect. Its older integration
fixture was migrated to declaration-before-payment order and the current tag.

Validation: 640 decision checks, 44 RL policy checks, tournament integration,
and Cerulean's targeted end-to-end integration scenario pass.

Fresh seed 16000001: ten paired Guo Jia games per chooser, five opponents in both
seats. All 20 completed; both historical frozen and current heuristics won 8/10.
All ten paired outcomes were unchanged. Current learner fast-action selections
included Cerulean twice, Missile twice, and Fracturize three times; frozen selected
none of those in those windows. The bank contains 2,915 review positions. This
is evidence of exercised coverage, not demonstrated win-rate improvement or
patch-isolated attribution. No model was trained or promoted.

Artifacts: `DevTools/rl/artifacts/ga-guojia-heuristic-screen-20261030/`.
Next: review the first divergent decisions and Lorraine's unresolved graveyard
choices before increasing training volume.

## Divergence review and Lorraine Kindle

Guo Jia's six first divergences all have identical recorded observations before
branching: games 2/8 use Decree instead of pass, game 4 uses Missile, game 6 uses
Fracturize, and games 5/7 change a Missile declaration target from the friendly
champion to an opposing unit. None changes the paired outcome; target correction
is directly observable but no causal win/loss label is inferred. Review details:
`ga-guojia-heuristic-screen-20261030/divergence-review.json`.

Lorraine's repeated graveyard gap was KindleProcess, with a separate Restorative
Flame recovery choice. Kindle now spends eligible fire cards toward the already
chosen activation, prefers March Hare with its element bonus, preserves floating
memory when an ordinary fire card is available, and stops at both remaining cost
and Kindle limit. Both-seat tests execute the chosen handler and confirm March
Hare returns to the field. Decision tests: 652 passed; policy tests: 46 passed;
tournament integration passed.

Replay steps now include `publicGraveyardChoices`, a review-only mapping of offered
public graveyard references to card IDs. Hidden zones are excluded. Existing model
action keys and observations are unchanged; old replay identities are not guessed.

Fresh Lorraine screen, seed 17000001: all 20 games completed; current and frozen
both won 8/10, with zero improved or regressed paired outcomes. Current selected
Kindle 47 times across ten games, including March Hare three times; frozen never
selected it. The new bank has 3,538 positions. `kindle-review.json` records the
actual public candidates and selections for review, without quality labels.
Artifacts: `DevTools/rl/artifacts/ga-lorraine-heuristic-screen-20261030/`.
This historical-baseline screen is not a patch-isolated win-rate experiment.
No model was trained or promoted. Next: Red Hare discard/draw and Restorative
Flame's optional graveyard payments, with explicit resource-preservation cases.

## Red Hare and Restorative Flame

Red Hare now accepts its discard/draw trigger when a live hand card has a known
graveyard payoff (March Hare or floating memory), and its subsequent discard
choice selects that card. It declines when the draw is blocked or unsafe, or
when the hand has no understood discard payoff. This conservative rule does
not attempt general hand-quality prediction or inspect the next draw.

Restorative Flame now scores both fast-action activation and all three optional
graveyard payments. It requires useful unblocked recovery, prefers ordinary
fire fuel, and preserves March Hare/floating memory unless remaining life is
five or less. Each payment is reconsidered against current damage. Activation
also waits for a pending friendly copy and retains existing affordability checks.
These are initial heuristics; the five-life urgency threshold is not learned.

Both-seat regressions execute Red Hare's discard/draw and Restorative Flame's
first recovery through the real decision queue, then verify conservation and
blocked recovery. All 678 decision checks and 46 RL policy checks pass. No new
full-game screen has been run for this batch; the prior Lorraine 8/10 result
predates these changes. Next: fresh paired Lorraine evaluation with these two
policies, then review changed choices before training or promotion.

## Lorraine recovery screen

Fresh seed 18000001, ten matched games per chooser against five opponents in
both seats: all 20 games completed. Frozen and current both won 7/10, and all ten
paired outcomes were unchanged. Current accepted Red Hare twice and declined
three times; frozen declined nine times. These opportunity counts are not
matched after the games diverge. Current discarded March Hare and Singed Emotions
in game 4 (steps 75 and 124). Current cast Restorative Flame in game 7, paid with
Intensified Pyre at step 165, and passed at step 166. The next observation's ownHP
feature rose from 14/30 to 16/30, consistent with two recovery; no further public
graveyard candidates were recorded at the pass. This pass does not establish a
successful resource-conservation decision.

Nine pairs had a first action divergence with matching recorded observations;
one pair had identical action sequences. The comparison bank has 2,873 positions.
Artifacts: `DevTools/rl/artifacts/ga-lorraine-recovery-screen-20261030/`, including
`decision-review.json`. No action-quality labels were inferred from final results.
The historical baseline includes earlier policy changes, so this is not an
isolated test of Red Hare/Restorative Flame. The two accepted Red Hare triggers
and one Flame activation are sparse evidence; no win-rate improvement is shown.
No model was trained or promoted.

Next: build matched continuations from selected decision positions to compare
resource costs and downstream choices directly, rather than repeatedly expanding
whole-game screens with very few occurrences of the abilities under review.

## First alternative-choice continuation

Added the offline `compare_ga_position.py` / `ga_position_worker.php` tools.
Captured two fresh Lorraine games with an exact initial-snapshot checksum (the
older initial-game hash predates remembered deck-list metadata and cannot verify
the saved text). Reconstructed game 1, decision 93, checking recorded public
observations and acting seats along the prefix. All three independent processes
agreed on the reconstructed complete state hash and candidate actions.

Kindle choices: graveyard slot 0, slot 1, or pass. With continuation seed 19000001,
current heuristics for both seats and a 300-action limit, all three branches won
for seat 1. Either banish branch completed in 82 actions; pass completed in 125.
The initial 100-action screen was extended because pass was unfinished, not a
loss. These are action counts, not turn counts or a proof that one choice is
better. No automatic preferred-action label was assigned.

Artifacts: `DevTools/rl/artifacts/ga-position-capture-20261030/`, particularly
`kindle-93-comparison.json`. Three tool tests pass (process isolation, state-drift
rejection, corrupted-snapshot refusal); a real prefix and all three continuations
completed. No model trained or promoted. Next: apply this tool to Red Hare and
recovery decisions with several continuation seeds and inspect resource tradeoffs.

## Red Hare and recovery alternative continuations

Recaptured ten Lorraine games with verified snapshot checksums, then compared
three positions with continuation seeds 19000001–19000003 and a 400-action cap.
All 18 branches completed and won for the deciding player:

| Position | Alternative | Actions per seed |
| --- | --- | --- |
| Game 4 step 74, Red Hare with March Hare | Decline / accept | 155 / 187 |
| Game 4 step 123, Red Hare with Singed Emotions | Decline / accept | 106 / 138 |
| Game 7 step 165, Restorative Flame payment | Pay / pass | 12 / 11 |

Each alternative produced identical action traces across the three seeds.
The saved deck state and deterministic continuation supplied no new behavioral
variation here; these are three positions, not eighteen independent games.
Action count is not a quality objective: a shorter win does not establish better
resource management. Neither accepting Red Hare nor paying for recovery was
necessary to win these sampled positions under current heuristic continuations.
No evidence here justifies changing either policy or creating preferred-action
training labels. No model was trained or promoted.

The comparison CLI now accepts `--seeds` and retains full per-seed reports plus
win/loss/draw/unfinished totals from the deciding player's perspective. It rejects
duplicate seeds and reconstructed-position drift between seeds. Six tests pass.
Artifacts: `DevTools/rl/artifacts/ga-position-recovery-capture-20261030/`,
including the three `*-multi-seed.json` reports.

Next: select distinct pressured positions (low life, competing graveyard fuel,
multiple discard candidates), rather than spending more runs on continuation
seeds that do not change behavior. Seek outcome-changing or resource-sensitive
decisions before expanding training.

## Pressured-position review

Added `select_ga_positions.py` to find review candidates from recorded player
observations, without parsing private state. It prioritizes low life, recorded
lethal combat pressure, declined optional actions, and multiple alternatives.
Duplicate replay files and single-choice prompts are excluded. Two selector
tests and the six continuation tests pass.

Compared three additional positions at seed 19000001 with a 500-action cap:

- Game 5 step 100: three life, four Kindle fuel choices plus pass. All five
  alternatives lost for seat 1 after 20 actions under current continuations.
- Game 9 step 178: declined Red Hare. Decline won in 166 actions; accept won
  in 161 actions.
- Game 9 step 341: declined Red Hare. Decline won in three actions; accept
  won in four actions.

All nine branches completed. These sampled alternatives do not support changing
the policies. The game 5 result does not prove the position was unwinnable; it
only shows these initial choices followed by current heuristics all lost.
Earlier decisions in that game should be examined before collecting more
continuations of this late position. No training labels, policy changes, or
model promotion resulted.

Artifacts are `pressure-shortlist.json` and `pressure-{game}-{step}.json` in
`DevTools/rl/artifacts/ga-position-recovery-capture-20261030/`.

## Earlier Lorraine targeting failures

Tracing game 5 backward found self-damage at steps 55 (Rococo enter),
61 (Intensified Pyre), and 63 (Blazing Throw declared target). These effects
were missing from the damage-target scorer and used the generic first-choice
fallback. Implemented explicit damage scoring for all three, including Pyre's
target-controller graveyard threshold (two damage below eight cards, six above).
Scoring uses offered legal targets and visible state, preferring opposing units.
No generated card code changed.

Before the fix, all sampled step 55 and 61 continuations lost. At step 63,
one opposing-unit branch won while the recorded self-target lost. Two worker
candidates had the same semantic key but different continuation outcomes; this
comparison is diagnostic evidence, not a clean action-quality training label.
The targeting fix is independently supported by both-seat regression checks.
Replayed all six step-63 alternatives after the change; all completed. Artifacts:
`earlier-5-{55,61,63}.json` and `earlier-5-63-after.json` in the recovery capture
directory.

694 decision checks and 46 RL policy checks pass, plus PHP syntax and whitespace
validation. No broad win-rate claim, training, or model promotion. Next: inspect
why semantically duplicate continuation candidates diverge before using this
tool's outcomes as training evidence, then run a fresh paired Lorraine screen.

## Distinct targets and fresh Lorraine screen

Resolved the apparent duplicate-choice discrepancy at game 5 step 63: choices
3 and 5 target `theirField-0` and `theirField-3`, separate objects with the same
card ID/status/damage semantic key. They are not duplicate executable actions.
The position report now includes whitelisted executable `choices`, verifies
those across branch reconstructions, and includes them in multi-seed summaries.
Learned action keys remain unchanged. Eight comparison tests pass, including
separate-copy preservation and rejection when a target changes under an identical
semantic key. The six real branches reproduce their earlier outcomes; see
`ga-position-recovery-capture-20261030/earlier-5-63-distinct-targets.json`.

Fresh paired screen: seed 20000001, ten games each with frozen/current Lorraine,
2,000-action limit, frozen opponent pool. All 20 games completed. Frozen won
7/10 and current won 10/10: three improved outcomes, zero regressions, seven
unchanged. This compares cumulative current heuristics with the historical
baseline, not an isolated targeting patch. It is a small descriptive sample,
not evidence for model promotion. No training or automatic quality labels.
Artifacts: `DevTools/rl/artifacts/ga-lorraine-targeting-screen-20261030/` contains
both replay sets, comparison, and a decision bank. These reviewed seeds must
remain excluded from training. Next: review the three changed outcomes, then
validate on an independent seed batch before claiming a durable improvement.


## Independent Lorraine validation and changed-outcome review

Reviewed the three improved screen games at their first divergent decision;
public observations match across each pair:

- Game 1, step 65: Blazing Throw changes from friendly `myField-1` to enemy Rococo `theirField-1`.
- Game 5, step 8: Kindle changes from passing to using Restorative Flame in the graveyard.
- Game 8, step 213: Smoke Bombs activates instead of passing during recollection.

These are review leads, not isolated causal explanations for the eventual wins.
In particular, the combined screen measures more than the damage-targeting fix.

Independent batch, seed 21000001, same ten-game paired schedule and 2,000-action
limit: frozen 7/10, current 8/10; one improved outcome, zero regressions, nine
unchanged. All 20 executions finished. The improved game 8 first diverges at
step 46: Blazing Throw targets `theirField-0` instead of `myField-1`, again with
identical public observations. The two remaining current losses are games 1 and
2 against Merlin, in opposite seats. Review these before further tuning.

Across these two batches: frozen 14/20, current 18/20, four improved outcomes,
zero regressions, sixteen unchanged. This is descriptive evidence from a small
schedule with paired seats, not a significance test or broad matchup guarantee.
No runtime changes, training, promotion, or action-quality labels in this pass.
Both batches remain evaluation-only and must stay out of training.

Artifacts: `DevTools/rl/artifacts/ga-lorraine-targeting-validation-20261030/`
contains frozen/current replays, the comparison, decision bank, and
`divergence-review.json` with exact choices for all four reviewed improvements.


## Merlin loss triage

Reviewed both remaining current-policy losses in the independent Lorraine batch.
Three concrete bot-scoring gaps have implemented engine abilities:

| Replay position | Missing bot scoring | Continuation results |
| --- | --- | --- |
| Game 1, step 58 | Rending Flames: banish three FIRE cards for double damage | Decline loses in 218 actions; accept loses in 214 |
| Game 2, step 41 | Hasty Messenger: optional discard to draw | Hand slots 0/1 lose in 122/134 actions; pass loses in 131 |
| Game 2, step 165 | Tariff Ring: optional banish activation | Activate loses in 14 actions; pass loses in 8 |

Verified all seven branches with the replay reconstruction guard, seed 22000001,
500-action horizon; all terminated. Current heuristics control both seats in
these continuations, unlike the paired tournament's frozen opponent. These
results do not prove choice equivalence, identify a causal mistake, or justify
learning a preference from survival length. No runtime changes or training.
Keep these positions evaluation-only, with quality labels unreviewed.

Artifacts in `ga-lorraine-targeting-validation-20261030/`: `review-1-58.json`,
`review-2-41.json`, `review-2-165.json`, and `merlin-loss-triage.json` distinguish
engine implementation from missing heuristic scoring. Next: inspect earlier
attack/development choices in the Merlin losses, and author independent tactical
positions for these scoring gaps (double-damage lethals, discard value versus
known draw benefit, and Tariff Ring's publicly observable tax opportunity).


## Hasty Messenger scoring coverage

Added optional discard scoring for `DsiRzt0trX:0:OnAttack-1`. Reuses the existing
Red Hare graveyard-value policy: prioritize March Hare, then floating-memory
cards; preserve cards without understood graveyard value. Uses only the bot's
own offered hand cards, deck count, and public draw restrictions. Declines when
drawing is blocked or only one deck card remains. No next-card inspection.

Independent synthetic tests in both seats verify selection over an ordinary
card, actual discard/draw resolution, preservation of ordinary cards, blocked
draws, and the last-card boundary. All 704 decision checks and 46 RL policy
checks pass; PHP syntax and whitespace checks pass. This closes a scoring gap,
not a demonstrated Merlin win-rate improvement. No training or promotion.

Rending Flames remains pending: its confirmation needs a coordinated three-card
payment policy and damage-prevention-aware combat valuation. Tariff Ring also
remains pending. The reviewed evaluation positions remain excluded from training.

### Tariff Ring scoring coverage

Added a conservative activation rule for Tariff Ring in the opponent's recollection:
spend it against two or more positive-power public allies, or one whose combined
visible power reaches our champion's remaining life. Rested allies count because
this window precedes waking. Preserve the consumable on quieter boards, outside
its legal phase, and when a Ring effect is already active or pending.

This is an estimate of upcoming pressure, not a claim that an attack is legal,
lethal, or prevented. It does not inspect opposing hand identities or deck order.
Champion/weapon-only pressure and optimal stacking of multiple Rings remain
outside this initial rule.

Both seats cover low/high pressure, removed allies, pending/resolved tax and
phase restrictions, including selecting the activation through the legal chooser.
Validation: 724 decision checks and 46 policy checks pass; PHP lint and diff
whitespace checks pass. No new tournament, RL training, or model promotion;
no win-rate improvement claimed. Rending Flames remains the next scoring gap.

### Rending Flames scoring coverage

Added an explicit `RendingFlamesConfirm` policy: spend three FIRE graveyard cards when a positive current attack crosses the opposing target's remaining-life threshold after doubling. Decline after damage, when doubling already applies, with insufficient fuel, or when already lethal / still short. Payment ranks offered cards to preserve floating memory and March Hare. Only public combat/graveyard state is read.

Protection handling is intentionally conservative: unknown target turn effects (except the entered-this-turn marker), opposing global effects, and a list of known prevention permanents cause a decline. This is not a complete damage simulator; other passive/replacement interactions and responses remain unmodeled. It can miss worthwhile boosts, especially sustained champion pressure and protected targets whose protection could be overcome. No guaranteed-kill or win-rate claim.

Validation: 740 decision checks and 46 policy checks pass. Both seats exercise the legal YESNO chooser, three-card payment, actual banishment/doubling continuation, already-lethal and insufficient-damage preservation, barrier preservation without scoring side effects, and the post-damage guard. PHP lint and diff whitespace checks pass. No training, tournament, or promotion run for this change. Next: replay the reviewed Rending Flames position and evaluate these three new policies together on fresh paired seeds.

### Optional-policy paired screen and replay repair

Fresh Lorraine paired evaluation (`ga-lorraine-optional-screen-20261030`, seed 23000001, 10 games per chooser, 2000-action cap) completed all 20 games. Current won 8/10 versus frozen 7/10: one improved outcome, zero regressions, nine unchanged. This compares the cumulative current heuristic with the historical frozen baseline, not an ablation of Messenger/Ring/Rending. The improved game 6 first diverges at step 45 on KindleProcess (PASS versus myGraveyard-0), with identical public observations. No causal attribution or promotion claim; seeds remain evaluation-only.

Replaying the reviewed Rending position initially exhausted 500 actions by repeatedly selecting a no-op field action. The offline position worker lacked the state-specific no-op exclusion used in normal bot execution. It now excludes an exact attempted action only for its unchanged comparable state, records noOpAttempts, and rejects exhausted candidates or a synthesized repeated no-op rather than silently spinning. Initial forced choices are still attempted, and no-op attempts remain in the action budget/trace.

Re-run at original seed 22000001: both branches terminate, with two no-op attempts each. NO loses after 254 actions; YES loses after 219. These results establish neither a better choice nor equivalent quality. Actual replay exercised the repair; all 8 comparison-tool tests and PHP lint pass. Next useful work: examine remaining loss positions and isolate the new optional policies against a recent baseline before claiming benefit from them.

### Full opponent-pool screen

Artifact: `DevTools/rl/artifacts/ga-three-archetype-broad-screen-20261030`.
Fresh seed 24000001; each priority archetype faced all 20 other configured decks,
both learner seats, with 40 games per chooser and a 2000-action cap. All 240 games
terminated normally. Current and historical frozen learners faced the same frozen
opponent policies; comparison guards verified sources, decks, schedule and starting
states. No runtime edits, training or promotion during this evaluation.

| Learner | Frozen wins | Current wins | Improved pairs | Regressed pairs |
| --- | ---: | ---: | ---: | ---: |
| Tristan Shadowstrike | 16/40 | 17/40 | 1 | 0 |
| Lorraine Red Hare | 32/40 | 38/40 | 6 | 0 |
| Guo Jia Genbu | 33/40 | 32/40 | 0 | 1 |

These are descriptive results from one paired seed per matchup, not statistical
proof or human-level competence. They broaden opponent coverage without varying
opponent policy strength. Aggregate current wins are 87/120 versus 81/120 frozen.

Tristan lost both seats against eleven opposing archetypes, including Lorraine,
Guo Jia, Merlin, Alice and Dante. Its `loss-review.json` retains twelve review
candidates involving champion entry, Dungeon Guide, Innervate and preparation.
Missing heuristic scoring does not establish missing engine implementation or a
causal mistake. Prioritize these recurring weaknesses over more Lorraine tuning.

Guo Jia's sole regression is game 8. First divergence is step 8 with identical
player observations: frozen passed, current cast Cerulean Decree. Saved
`guo-jia/regression-review.json` as an unreviewed lead; continuation comparisons
are needed before attributing the loss to that cast. Lorraine's only current
losses were one seat each against Alice and Dante. Per-archetype banks and paired
comparisons are saved alongside all replays. All positions remain evaluation-only.
