# GrandArchiveSim fixture roadmap

Status as of 2026-10-11, branch `codex/ga-fixture-scenario-builder` (rebased on `origin/main`, not pushed).

## Where we are

| Measure | Count |
| --- | ---: |
| Fixtures | 1,493 |
| Written as scenarios (builder layer 1) | 1,261 |
| Still inline in the generator | 4 families (3 loops + `fractal-of-insight-decline-no-glimpse`) |
| Complete semantic contracts | 1,127 |
| Incomplete semantic contracts | 162 |
| Legacy (no semantic contract) | 204 |
| Fixtures flagged by `lint-fixture-coverage.php` | 478 |
| Implemented cards | 2,477 |
| Cards with at least one fixture | 935 |
| **Cards with no fixture** | **1,542** (all outside the 619 starter-deck cards) |
| Cards with only a legacy fixture | 28 |
| Known generator drift (pre-existing) | 24 fixtures drift, 3 fail generator verification |

Uncovered cards by type: ACTION 449, ALLY 420, ITEM 175, UNIQUE ALLY 101, ATTACK 69, CHAMPION 61,
PHANTASIA 68, WEAPON 48, DOMAIN 39, LESSER BOON 26, other 21.
Uncovered by number of abilities: 1 ability 1,372; 2 abilities 164; 3 abilities 6.
Uncovered by mechanic tag (a card can have several): condition 836, trigger 511, targeting 478, cost 471,
zone-movement 464, draw-discard 323, damage 306, status 300, combat 263, counter 232, token 94, recover 88.

What exists is **layer 1**: a fixture is described in card names and verbs (`Scenario.php`), the generator,
runner and fixture files are unchanged, and `register()` plus `verify-ga-scenarios.php` prove equivalence.
What does not exist is **layer 2**: shape-level helpers that make a whole family one or two lines.
The damage fixtures use layer 1 only; each is still 8 to 12 lines.

## Principles

1. **Equivalence before new behaviour.** Any refactor of existing fixtures is proven with the register() guard and
   `verify-ga-scenarios.php` (zero DIFF beyond the known drift list).
2. **A fixture that cannot fail is worse than none.** Every new assertion is mutation-tested once. The generator
   silently accepts an answer to the wrong decision, so assert observable state.
3. **Each new fixture is also a bug hunt.** Failures become engine fixes under the existing per-chunk rhythm
   (fix commit, fixtures, suite + GABot, docs/backlog regenerate).
4. **Ask before behaviour-wide engine changes.** Fixture work never changes shared engine semantics without a check-in.
5. **Generated files are never hand-edited.** Regenerate with `php zzGameCodeGenerator.php rootName=GrandArchiveSim`.

## Phase 0: Land layer 1 (DONE 2026-10-11)

- The PR for `codex/ga-fixture-scenario-builder` is **held until the end of the roadmap** (user decision); the branch stays local and rebased on `origin/main`.
- All 22 drifting fixtures resolved (see "Drift triage" below). The full `verify-ga-scenarios.php` run (1,261 scenarios) plus the 15 family scenarios match HEAD by
  meaning, except `diao-chan-enchantress-enter-glimmer` (an intentional fixture upgrade) and the removed orphan below.
- The generator holds no fixture definitions any more: the loop and helper families (Shackled Theurgist x6, Bygone Days x2, Heirloom x2,
  Lamentation's Toll x2, Seeker's Aetherwing x2) and `fractal-of-insight-decline-no-glimpse` (new `base()` verb) are scenarios in
  `families.php`, and 19 dead `zz-probe-*` definitions (never produced a fixture) were removed. 29 -> 0 inline `$fixtures[...]` statements.
- The orphan scenario `pouvoir-absolu-banish-top10-omen` (no assertions, never had a committed fixture, superseded by the semantic
  Pouvoir Absolu fixture) was removed.
- Not done: "add the verifier to CI". There is no CI configuration in the repository (`.github` only holds agent instructions), and the
  full verifier takes ~12 minutes with 6 workers. Deferred to Phase 5 as a decision.
- Left alone: the untracked probe scripts (`DevTools/_smoke_probe.php`, `_undef_funcs.php`, `_undef_handlers.php`); they are working
  tools the memory notes refer to, not part of this change.

Exit met: no inline fixture definitions; drift list empty. Suite 1493/1493, GABot 10/10.

## Phase 1: Layer 2, shape helpers on existing fixtures (equivalence-proven)

Each step collapses an existing family and is proven with the guard and verifier. Order by payoff and certainty.

1. **`castSpell(card, pay:, window:, target:, expect:)`** with `expect` builders (`damage(n)`, `destroyed`, `moved(zone)`,
   `drew(n)`, `counter(name, n)`). Pilot on the 25 damage fixtures plus the 3 Thunderclap ones (~3 lines each), then roll
   to the ~190 spell-shaped fixtures.
2. **Printed-cost family** (67 `…-pays-its-printed-cost`): `costFodder()` helper plus a table of (card, cost).
3. **Spellshroud family** (20 `…-spell-cannot-target-it`): one table of (spell, target zone).
4. **Declined-optional family** ("decline first target still offers second", ~15): `declines(step)` verb.
5. **Class-bonus / level-threshold family** ("level N costs less", "class bonus …"): `atLevel(n)`, `withClassBonus()`.
6. **Coverage in code:** `covers()` currently appears in 3 scenarios. Add `expect*` helpers that auto-derive
   `semanticCoverage` from the verbs used (cost -> `cost`, `target` -> `targeting`, `expectDamage` -> `damage`),
   leaving `covers()` for rules text only. This closes most of the 162 incomplete contracts for free.
7. **`expectRaw`** for the 575 scenarios that still carry hand-written assertion JSON.

Exit: top 10 shapes (284 fixtures today) are each expressible in one statement; regenerating everything is still byte-identical by meaning.

## Phase 2: Raise quality of what already exists

- **162 incomplete contracts** (phase 1 step 6 clears most; finish by hand).
- **204 legacy fixtures**: add semantic assertions; prioritise cards also in starter decks.
- **478 lint flags**: triage into real under-testing (add assertions) versus lint false positives (fix the lint; the
  slug-contains-"level" false positive is the known one).
- **28 cards with only a legacy fixture** become real semantic fixtures.
- Add a mutation spot-check script: for a sample of fixtures, flip one expectation and confirm the generator fails.

Exit: complete semantic contracts 100% of fixtures; lint at zero warnings; audit `--strict` enabled in CI.

## Phase 3: Cover all 1,542 uncovered cards, batched by element

Decision (2026-10-11): scope is **all 1,542 cards**, worked one element at a time. Within an element the cards share a
champion lineage deck (one `decks.php` template per element, reused by every fixture), element-bonus rules, and
recurring mechanics, so helpers and bug fixes carry over between cards. Counts are measured from the backlog JSON
(Exalted variants are folded into their base element).

| Batch | Element | Cards | Dominant types | Dominant mechanics |
| --- | --- | ---: | --- | --- |
| 3a | FIRE | 236 | ally 97, action 81 | damage 114, condition, trigger |
| 3b | WATER | 262 | ally 106, action 80, phantasia 21 | cost 111, zone-movement 111 |
| 3c | WIND | 251 | ally 98, action 84 | targeting 91, trigger 87 |
| 3d | NORM (neutral) | 531 | ally 177, action 126, item 93, champion 29 | condition 278, zone-movement 155; split by class (Cleric 151, Tamer 90, Warrior 90, Guardian 76, Ranger 56, Assassin 52) |
| 3e | Minor elements: Umbra 63, Astra 37, Tera 35, Exia 31, Neos 29, Arcane 17, Luxem 15, Crux 14 | 241 | mixed | Exia/Umbra damage, Neos tokens |
| 3f | No element / no card data | 21 | ids only | inspect first: likely tokens or stale ability registrations |

Order inside every element batch (cheapest and most reusable first):
1. Damage and single-effect spells (`castSpell` + `expect` helpers).
2. Keyword-only and vanilla allies (a handful per element; see "Allies" below).
3. Allies with `On Enter` / `On Death` / combat triggers.
4. Class-bonus and unique cards (needs class lineage in the element deck).
5. Items, regalia, weapons, phantasia, domains, boons (activated or persistent abilities).
6. Multi-ability cards, one fixture per ability.

Because NORM is 531 cards (34%), it is split into six class sub-batches, and every element batch ends with a
cross-element sweep: the same card in a different element's deck only when its text refers to elements.

Per card the minimum is one positive path and, where the text has a condition, cost or target restriction, one negative path.

Allies (543 uncovered): on enter 175, class/unique bonus 134, static/other 82, combat trigger 46, on death/leave 30,
activated 29, other trigger 20, keyword-only 16, vanilla 11. Only 27 are keyword-only or vanilla; the earlier "~200
vanilla allies" estimate was wrong.

Known engine limits that will make some cards unfixable until the engine changes (flag, do not paper over):
targets are chosen at resolution, not declaration (Revealing Mesmer, Diana Moonpiercer); several card names are absent
from the card data (Orb of Sealing, Tricastles of Lucenia, Redirect Flow).

Exit: every implemented card has at least one semantic fixture or is listed in `UNFIXABLE.md` with the reason.

## Phase 4: Scaffolding and automation (runs alongside 3)

- **`DevTools/ga-scaffold.php <card>`**: read the backlog JSON and the card dictionary, emit a scenario skeleton
  (deck, seed, play/pay from printed cost, `covers()` with rules text prefilled) for a human or Claude to finish.
- **Generated-from-card-data fixtures, limited to what card data fully determines:** printed-cost fixtures (cost paid = printed reserve) and the 27 keyword-only/vanilla allies (enters with printed power/life, enters rested for Hindered, keyword behaviours). Everything with rules text beyond that is hand-written.
- **Probe integration**: promote `_smoke_probe.php` into `DevTools/probes/` with a mode that lists cards whose probe run
  shows errors, so new fixtures are written against known-risk cards first.
- **Backlog report** lists the next batch automatically (`build-ga-semantic-backlog.php` already does the ranking).

## Phase 5: Guard rails

- Scenario-first rule documented (already in the guide) and enforced: the generator rejects new inline definitions.
- CI: suite, GABot, `verify-ga-scenarios.php`, `audit-ga-semantic-coverage.php --strict` at the agreed baseline.
- Quarterly drift check (`audit-fixture-drift.php`) so recorded snapshots do not rot like the 24.

## Suggested order

0 -> 1.1 -> 1.2/1.3 -> 1.6 -> 3a Fire (first real new coverage on the new helpers; damage is Fire's top mechanic) -> 3b Water -> 3c Wind -> 3d Norm by class -> 3e minor elements -> 3f, with phase 2 in parallel and phase 4 scaffolding built before 3a. Phase 5 once the baseline is reached. The PR comes last.

## Drift triage (resolved)

Cause: hand edits to committed fixture files were never mirrored into the generator definition, so the definitions went stale.
Regeneration replays the definition, so it would have re-recorded broken versions.

- **Extra priority-window passes** (a fast card in hand opens a window at each turn boundary): 13 fixtures had `-`/`PASS` rows added by
  hand (aenean-frozen-shunt, cosmic-astroscope, ingredient-pouch, keen-tidebinder, lurking-assailant, piquant-shieldbearer,
  plated-bullet, prototype-staff, rousing-slam, smash-with-obelisk, sweet-ambrosia, venous-core, wind-resonance-bauble).
- **Answers that were silently dropped** (the original definition answered a target prompt with PASS, which the engine accepts as a
  decline): training-session, savage-attack, spirit-blade-ascension, phalanx-captain, spirit-blade-dispersion, flute-of-taming
  (the definition even activated ability 1 where the committed fixture uses ability 0).
- **Metadata**: `key-slime-pudding…` and `redslime-ondeath…` list extra `testedCards` in the committed `meta.json`.
- **Real fixture upgrade:** `diao-chan-enchantress-enter-glimmer`. The committed fixture had the starting champion pre-set to Diao Chan
  (the glimmer counters were baked into the initial state, so the assertion proved nothing); its definition already levels up into her
  as a replayed action but its assertions still checked step 1. Assertions moved to step 3, the fixture regenerated, and the
  assertion mutation-tested (expecting 3 glimmer counters now fails).

Fix: every affected scenario now spells out the committed action rows (`fixdrift` rewrote the chain from the committed `actions.json`),
verified by regenerating and comparing by meaning.

## Decisions

1. PR/push: held until the end. (decided)
2. Drift fixtures: investigated per fixture; scenarios now match the committed files. (done)
3. Phase 3 scope: all 1,542 cards, batched by element. (decided)
4. Auto-generated fixtures: limited to scaffolding and the tiny keyword/vanilla set (see Phase 4). Open.
