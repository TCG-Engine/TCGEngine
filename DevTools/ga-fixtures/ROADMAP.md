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

1. **`castSpell(card, pay:, window:, target:)`**. DONE 2026-10-11: added and applied to all 379 single-line
   `play`/`pay`/`pass`/`target` chains (28 damage fixtures first, then every other scenario file); the full verifier
   still matches HEAD for 1,260 of 1,261 (the one miss was a stale slug in my list). The `expect` builders
   (`damage(n)`, `destroyed`, `moved(zone)`, `drew(n)`, `counter(name, n)`) are folded into step 6 with the derived `covers()`.
2. **Printed-cost family** (67 `…-ability-N-pays-its-printed-cost`). DONE 2026-10-11: `gaAbilitySandbox(slug, card,
   classes:, cardPatch:, champion:)` builds the shared board and `expectCostFootprint(hand:, memory:, banish:,
   graveyard:, field:)` the five zone-size assertions; all 67 regenerate to HEAD by meaning (2,665 lines became 136).
   The footprint labels still carry template text ("(N)", "CARDNAME"); making them card-specific is a Phase 2 item.
3. **Spellshroud family** (20 `…-spell-cannot-target-it`). DONE 2026-10-11: `gaSpellshroudCase(slug, holders, spell,
   holderCounters:, word:, oppChampion:)` with three spell kits (Liturgy of Corruption, Fracturize, Disenchant); 19 of 20
   regenerate to HEAD by meaning. Left hand-written: the Rhongomiant own-spell variant (the controller's own spell is refused).
4. **Declined-optional family**. DROPPED (2026-10-11): 102 slugs mention "decline", but a decline is the same input as a
   priority pass (`pass(1)`), so a `declines()` verb would only rename it, and telling a decline from a window pass needs
   per-fixture judgment. No mechanical payoff; the prompts that matter are covered by `rejects()` and `answer()`.
5. **`readyFor(card, classBonus:, level:, damage:)`**. DONE 2026-10-11, replacing the planned `atLevel` / `withClassBonus`
   (`champion(level:)` already exists; the real saving is not looking up which lineage card unlocks an element or which
   class a card needs). It derives element access from the card's element (table of one lineage card per advanced
   element) and the class bonus from its class. 50 existing scenarios converted and regenerate to HEAD. Phase 3 starts every
   fixture with it.
6. **Coverage in code**. REVISED. Measured: the 162 incomplete contracts are missing rules clauses (76), semantic labels on
   assertions (43) or observable evidence beyond a queue-empty check (43). None are missing `mechanics`, so deriving
   mechanics from verbs would not close any, and a derived `covers()` would rewrite meta that the generator keeps by hand.
   Done instead: `DevTools/ga-card-info.php "Card" ...` prints id, element, type, classes, costs, stats and effect text, so
   the clause text for a fixture comes from card data. Closing the 162 is Phase 2 hand work.
7. **`expectRaw`**. DEFERRED to Phase 2: the 575 scenarios with hand-written assertion JSON keep it in the fixture files,
   so the generator preserves it today. Moving it into the scenarios makes them the only source of truth (no drift) but is
   large mechanical churn with no behaviour change; do it when a scenario file is touched anyway.

Exit (met 2026-10-11 for the shapes that proved regular): `castSpell` (407 scenarios), `gaAbilitySandbox` (67), `gaSpellshroudCase` (19), `readyFor` (50). Regenerating everything is still identical by meaning.

## Phase 2: Raise quality of what already exists

- **162 incomplete contracts**. DONE 2026-10-11, 0 left. Causes and fixes: 76 had no rules clause (written from card text);
  43 were wrongly reported because the audit did not count `card_exists` / computed power / life as observable evidence
  (tool fixed); 43 had recorded state but no semantic label (labelled, with 8 given new observable assertions). Three
  fixtures proved nothing and were rebuilt: the Beastbond Ears / Deep Sea Beastbonder / Melodious Flute level bonus proofs
  relied on a decision appearing (Flute's pass answered the Glimpse prompt, so the glimpse never ran). Each now asserts
  the drawn card and has a negative twin. A Weaponsmith note that claimed the trigger fired three times was wrong
  (the weapon enters with 2 durability counters).
- **478 lint flags -> 11**. DONE 2026-10-11 for the heuristic: the slug-keyword check now skips fixtures that have a complete
  semantic contract, which removed 357 false positives; 22 of the remaining 33 were zero-assertion fixtures and got observable
  assertions plus contracts. The last 11 are hand-maintained snapshot fixtures (below).
- **Cards with only a legacy fixture: 28 -> 0**. DONE 2026-10-11: 6 were left after the contract work; each now has semantic
  fixtures with negative twins (`legacy-only-cards.php`).
- **Mutation spot-check**. DONE 2026-10-11: `php DevTools/mutation-spot-check.php --sample=N --seed=S` changes one expected
  value in a sampled fixture's semantic assertions and requires the replay to fail. 72 sampled mutants, 72 killed.
- **182 legacy fixtures (no semantic contract)**. OPEN. 178 are hand-maintained snapshot fixtures (generated from the
  rich-board template or by the original author) with no notes and large boards, so each one needs its card read and its
  state interpreted by hand. The 11 still flagged by lint are among them. Their cards already have semantic coverage from
  other fixtures except where noted, so this is quality work rather than coverage. Recommendation: do it opportunistically
  (when a fixture breaks or its card is touched) and put the effort into Phase 3.
Exit: complete semantic contracts on every fixture that is not a legacy snapshot (met); lint at zero warnings (11 left, all legacy snapshots); audit `--strict` enabled in CI (needs CI, Phase 5).

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

- **`DevTools/ga-scaffold.php <card>`** -- DONE 2026-10-12 (built after Fire and Water, before Wind; see the guide's "Starting a fixture"; `--probe`, `--list`, `--write`, `todo-` slug guard in `register()`, plus `DevTools/ga-fixture-run.sh`): read the backlog JSON and the card dictionary, emit a scenario skeleton
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


## Progress log

### Phase 3a (Fire), started 2026-10-11
- 125 cards covered so far (uncovered cards 1,548 -> 1,423); Fire went from 236 to about 120 uncovered. Done: damage/destroy/draw/
  distant/imbue/sweep spells, the 11 attack cards, items, phantasias, domains and boons, and about 45 allies (static
  modifiers, On Enter, On Attack, On Death, activated). Files: `fire-spells.php`, `fire-attacks.php`, `fire-allies.php`,
  `fire-items.php`.
- Engine bugs found by the new fixtures and fixed (each has a regression fixture): Fling Food and Shield Fragmentation never paid
  their sacrifice cost; Fireblooded Oath never paid its graveyard-banish cost; Absolving Flames banished one card instead of four;
  Searing Rebuke dealt its Class Bonus damage on resolution instead of when damage is prevented; Peppered Chef tagged a stale slot
  after its sacrifice; five "+N POWER until end of turn" buffs (Blazing Bowman, Ash Filcher, Tricky Chimps, Stolid Vanguard, Primal
  Whip) never applied; Scorchfire Assassin's per-counter buffs did not stack; Leran and Where Futures Stir end-phase effects only
  ran in the goldfish helper. Open: Molten Impact sacrifices at resolution instead of as a cost.
- Authoring loop that works at about 25 cards per batch: draft a bare `castSpell` scenario, run `DevTools/ga-autoplay.php` to see
  the real prompt order, write the scenario with those answers, `DevTools/ga-fixture-try.py` to settle priority windows, and
  `dump-fixture-state.php` when an assertion fails. New scenario verbs: `observe()`, `reachTurnThree()`, `attack()`,
  `expectPower/expectLife/expectCardExists`.
- Not yet done in Fire: remaining allies (about 60: Cardistry hearts, Kindle/Imbue costs, stealth/ranged keywords, Pride allies),
  weapons, regalia items, champions and the ten odd actions (Attune with Flames, Oppressive Presence, Suffocating Ash,
  Tether in Flames, Surge Protector, Suzaku's Command, Innervate Fury, Feu Awakening, Molten Echo, Ignis Deus).

### Phase 3a (Fire) continued -- allies batches 3 and 4 (2026-10-11)
- 33 more allies fixtured (`fire-allies-b.php`, `fire-allies-c.php`): ranged/distant, stealth, Cardistry hearts, foster, Kindle,
  Pride/level, Priestess/Cinderbloom gather, Templar/Witch/Ephemerate. Nine earlier fixtures had no `->tested()` card and so counted
  for nothing in the audit; fixed, and `Scenario::build()` now throws when `covers()` is used with no tested card. Covered cards 1,071 -> 1,112.
- Engine bugs fixed (each with a fixture):
  - Hand-maintained keyword registries were missing cards, so the printed keyword silently did nothing: Ranged (Ashfletched Bowman,
    VelTech Armiger, Nimble Longbowman, Huang Zhong, Acheron Express Officer, Rampart Defender), Ephemerate (Emberwrath Witch, Peer
    Beyond, Classical Opening, Distort Reality), Kindle (Silent Firebrand), Cardistry (Eight of Hearts), Imbue (Tempestuous Seraphim).
  - Eight of Hearts' Cardistry draw was filed as a play effect: casting it drew two cards for free and the field ability did not exist.
  - Emberwrath Witch only tagged itself at Enter; nothing ever sacrificed it at the end phase.
  - Generated `OnAttack` macro listeners were never dispatched (Inflamed Bladehand, Brooch X Ultra, AquaTech Shell); now dispatched
    from `OnAttackTrigger`.
- New `DevTools/audit-ga-keyword-registries.php` cross-checks every card's printed Ranged/Ephemerate/Kindle/Cardistry/Imbue/Link keyword
  against those registries. It lists three open gaps: Steady Sharpshooter (aim counters), Calculated Foresight (champion ranged 3) and
  Reconnaissance Field (REST ability), plus Ritai Stablemaster's "Horse cards have kindle 3".
- Open engine findings to review together once Fire is finished (see the end-of-Fire report):
  - Generated macro listeners for `AllyDestroyed` (Turbulent Bounty Hunter), `ActivateAbility` (Trained Birdroid) and `CardBanish`
    (Evaporation Synchron) are still never dispatched.
  - Eight of Hearts' alternative payment (sacrifice up to two Suited allies, 3 each) is not implemented.
  - Abnegation's conditional Ephemerate has no "activation negated this turn" tracker.
  - `[Class Bonus] Kindle` is not gated on the class bonus (Silent Firebrand, Burning Aethercharge, Glowering Conflagration).
  - Molten Impact sacrifices at resolution rather than as a cost.
  - Weapon On Attack closures receive the ATTACKER's mz as `$mzID`, so a "CARDNAME" in a weapon's On Attack body points at the wrong
    object. Firetongue (durability counter went on the champion) and Dragon's Dawn (+2 POWER / On Champion Hit draw tagged the champion)
    are fixed with `$customDQHandlers` overrides that use `GetCombatWeapon()`. Other weapons checked so far were correct.

### Phase 3a (Fire) -- weapons and regalia (2026-10-11)
- `fire-regalia.php`: Firetongue, Impact Hammer, Forgelight Blade, Dragon's Dawn, Heatwave Generator, Forgelight Scepter (odd and even
  memory) and Everflame Staff. The Dragon's Dawn "discard went to banish" worry was test setup (an unanswered prompt); the card goes to the graveyard.
- Still to do in Fire: remaining regalia (Beastsoul Visage, Blast Shield, Fanned Synchron, Infernal Vessel, Mantle of the Abyss, Sigil of
  Budding Embers, Bombard Flarecannon, Contraband Revolver, Lacuna's Grasp), 8 champions, ~12 odd actions, ~15 allies, ~19 uniques.

### Phase 3a (Fire) -- actions batch (2026-10-11)
- `fire-actions.php`: Tyrannical Denigration (x2), Spontaneous Combustion, Suffocating Ash (x3), Tether in Flames (x3), Suzaku's Command, Attune with Flames (x3),
  Oppressive Presence, Surge Protector, Potion Infusion: Blaze, Ignis Deus (x2), Innervate Fury (x2).
- Engine bugs fixed (each with a fixture):
  - Oppressive Presence could never be activated: its generated prereq was inverted (refused the one window it is meant for).
  - Innervate Fury never paid its "additional cost: delevel your champion and recover 5" (Innervate Agility had it, the sibling did not); also has a prereq now.
  - Split damage (Innervate Fury, Spirit Blade: Dispersion, ...) left a later target alive when an earlier one died, because the zone compacted and the next index shifted; damage is now dealt highest index first.
  - Potion Infusion: Blaze read the raw "CombatAttacker" mz (attacker's perspective) and so never hit the opponent's attacking ally.
- New findings (not fixed): ~20 other raw `GetVariable("CombatAttacker")` readers in GameLogic.php/GeneratedMacroCode.php resolve the attacker from the acting player's perspective; any
  defender-side reaction that uses it can mis-resolve (audit list in the end-of-Fire report). Suzaku's Command's Shenju clause is unreachable: every Shenju has Spellshroud and
  `FilterSpellshroudTargets` also hides it from its controller's own spells (rules question: does Spellshroud stop your own spells?).

### Phase 3a (Fire) -- allies, regalia and champions batch 5 (2026-10-11)
- `fire-allies-d.php`: Pyrolysis Sage, Serpentine Judicator, Flashfire Horse, Firetuned Automaton, Direwolf, Charged Gunslinger, Pupil of Sacred Flames, both second printings
  (Cheetah of Bound Fury, Firebird Trailblazer), General at Arms, Airship Captain, Lavasoul Tiger, Ritai Guard, Ritai Stablemaster, Outfitted Ravager, Blast Shield, Beastsoul Visage,
  Infernal Vessel, Fanned Synchron, Fang of Dragon's Breath and six champions (Aithne, Hanabi, Vyra, Fragmented, Fortuitous and Serene Spirit of Fire).
- Engine bug fixed: "As an additional cost to activate this card, discard a (fire element) card" was never paid by Firetuned Automaton, Dichroic Scorch or Plutus (the generated bodies only said
  "handled in DoActivateCard"). New `GenericDiscardCostSpec()` / `GenericDiscardCost` handler in GameLogic.php; the Dichroic Scorch fixture was recorded against the unpaid cost and was regenerated
  (the Baby Red Slime now lands in the graveyard and three cards, not four, go to memory).
- Finding: the starting champion enters with `SuppressNextEnter`, so a level 0 champion's printed On Enter (Fragmented / Fortuitous / Serene Spirit of Fire: Glimpse + draw, Hanabi / Vyra / Aithne: draw seven)
  never runs at game start; the opening hand is always seven. The fixtures seed the champion onto the field to exercise the ability.

### Phase 3a (Fire) -- unique allies and domains (2026-10-11)
- `fire-uniques.php`: Rouge (four thresholds), Plutus, Arthur, Volda, Xiao Qiao, Inzali, Red Hare, Tweedledum, Da Qiao, Lu Xun, March Hare, Verita, Duchess, Varuck, Gloamspire, Jianye.
- Engine bugs fixed (each with a fixture):
  - Duchess, Six of Hearts' Cardistry fataled the engine ("Call to undefined method stdClass::Remove()") the first time it was used, and ignored Exalted fire actions.
  - Gloamspire counted only memory as influence (both for its draw ability, so every player drew, and for its Upkeep, so it was never sacrificed).
  - The new discard additional cost listed the activated card itself as a discard choice, and Plutus / Dichroic Scorch / Firetuned Automaton lost the card if there was nothing to discard (refusal gate added).
- New findings (not fixed, need a decision): (1) 14 "EXALTED,FIRE" cards (Alizarin Longbowman, Queen's Cinderhog, Blaze Alight, Suffocating Ash, Tyrannical Denigration, Lavastorm, ...) are not "fire element"
  to ~33 exact `CardElement(...) === "FIRE"` comparisons: Kindle payment, fire discards, fire-source unpreventable damage, fire ally death triggers. (2) Abilities printed "Activate only at slow speed"
  (14 cards) are offered and usable in fast response windows: Gloamspire's draw resolved from the end-of-turn window. (3) (fixed afterwards) the Cardistry "Activate this ability only once" restriction was missing on Duchess and six other Cardistry cards. The full list is in `ENGINE-FINDINGS.md`.

### Phase 3a (Fire) -- wrap-up (2026-10-11)
- Fire is at 1,174 covered cards overall; 15 Fire cards remain and all need a bonus champion or a harder setup (see `ENGINE-FINDINGS.md`, section 4).
- `ENGINE-FINDINGS.md` is the whole-picture review of the engine bugs found so far: what was fixed, the six behaviour-wide items that need a decision, and the cards whose text is not implemented.
- Decisions taken at the end of Fire (see `ENGINE-FINDINGS.md`): slow-speed-only abilities are now enforced centrally; `[Class Bonus]` / `[Merlin Bonus]` Kindle is gated; Spellshroud keeps blocking
  your own spells; the Exalted dual-element fix is parked as its own batch.

### Phase 3b (Water) -- wrap-up (2026-10-12)
- Files: `water-actions.php`, `water-actions2.php`, `water-negates.php`, `water-negates2.php`, `water-attacks.php`, `water-allies.php`, `water-allies2.php`, `water-uniques.php`, `water-items.php`, `water-items2.php`.
  Floating Memory is covered for every card that has it with `gaFloatingPays` (a Lorraine level-up paid by banishing the card; [Class Bonus] cards also get a "cannot pay" twin).
- 36 Water cards remain; they need a bonus champion or mastery (Ciel omens: Pristine Scourge, Obsequious Blow, Frozen Divinity; Alice: Drown in Sorrow, Drifting Abysshell; Nico: Nico, Sablemere, Magebane Lash bonus, Ravishing Finale bonus;
  Merlin: Burnished Obelith; Kongming / Shifting Currents: Ceasing Edict, Ebbing Tide; Diao Chan: Captivating Opulence bonus; Guo Jia: Submerged / Pelagic Fatestone bonuses; Chessman / Pawn tokens: Weiss Bishop, Weiss Knight,
  Hunt, Skewering Advance), Gun/Bow loading (Purified Shot, Cascading Round, Loaded Thoughts, Mana's Cascade, Seafletched Serpent), Advanced Imbue (Fount Seraphim), level-up text with no observable (Fractal of Waves, Submerged Fatestone,
  Treacle), name-choosing cards (Nia, Jianyu, Total Whiteout), or unimplemented text (Calculated Foresight, Reconnaissance Field). Greater Boon of Kanaloa, Avalon, Xuchang, Frostbitten Etui, Confidant's Oath, Wildheart Lyre, Vaporjet Shield, Tideholder Claymore, Burnished Obelith have no fixture yet.
- Engine bugs fixed (each with a fixture): see the "Found while covering Water" and "Decided and fixed" tables in `ENGINE-FINDINGS.md` -- Hailfinch On Hit, Innervate Knowledge cost, Surging Obstruction (unimplemented), Song of Frost / Ravishing Finale / Intervention
  alternate costs never offered, Aenean Cryosalvo's Fractal bonus, level-up recovery healing the removed champion, Martial Flowstate sacrificing the new champion, hidden-card choice perspective, enterPrereqs, repeated deck-0 MZMove loops, Frigid Bash On Hit.
- Harness: scenario fixtures can no longer be silently truncated (`strictActions`, `--audit-actions`); `expectGlobalEffect`; remember that an `expect*` without a step is evaluated after the LAST action.
- Server note: run `php zzGameCodeGenerator.php rootName=GrandArchiveSim` after deploying (the `SkipRemovedMZMoves` generator module changes generated code).
