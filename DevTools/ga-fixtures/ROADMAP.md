# GrandArchiveSim fixture roadmap

Current status: 2026-10-16, branch `codex/ga-fixture-scenario-builder`. Push and PR remain held until roadmap closeout.

## Current status

- Phases 0 and 1 are complete: scenarios are the authoring source, inline definitions are rejected, and the useful family helpers are in place.
- Phase 2's main exit is met: zero incomplete semantic contracts. The latest Phase D run has 4,104 passing fixtures, 3,920 complete contracts and 184 legacy snapshots. GABot passes 10/10 and all local guards pass. Legacy assertion migration remains opportunistic quality work.
- Phase 3's exit is met: zero implemented cards lack semantic coverage and the exception baseline is empty. This is card coverage, not a claim that every rules clause is implemented.
- Phase 4: scaffold and fixture runner exist. Promoting the owner's untracked smoke probe remains open; automatic printed-cost/vanilla families remain a proposal.
- Phase 5: `ga-verify-all.sh` runs suite, GABot, strict contracts, action/inline guards, retained Phase C/D boundaries and the zero-card baseline. `--full` adds scenario regeneration. No CI service or quarterly execution schedule is configured.
- Nico and Ardus are closed, including Brookside Scout's weapon-choice sequencing and all three Ardus On Death effects. Ardus follow-up committed as `5d9516770`.
- Next engine work is tracked in the current-status section of `ENGINE-FINDINGS.md`. The dated sections below are history; their old counts and "open" notes are not the current backlog.

## Follow-up roadmap (2026-10-16)

This is the active plan after the coverage phases below. Phases A–G distinguish confirmed defects, investigations, test depth and tooling decisions. Historical "open" notes are not automatically confirmed defects. Planning these phases does not authorize deployment, scheduling, or behaviour-wide engine changes.

Completed baseline: Nico and Ardus (`a4fd23e48`, Ardus follow-up `5d9516770`); Fire activation costs and Brew (`952d4d07a`). Latest full verification: 3,955/3,955 fixtures and GABot 10/10. No new verification is implied by this documentation update.

### Phase A — Fix the confirmed card-local defects

Targeting batch validation (2026-10-16): 3,959/3,959 integration fixtures, GABot 10/10, strict semantic audit, action/inline guard, three-card baseline, PHP lint and diff formatting all pass. Each of the four new fixtures fails with the overrides disabled. Full scenario regeneration was not run.

Suzaku combat batch validation (2026-10-16): 3,964/3,964 integration fixtures, GABot 10/10, strict semantic audit (3,780 complete, zero incomplete, 184 legacy), action/inline guard, three-card baseline, PHP lint and diff formatting pass. Full scenario regeneration was not run. Changes remain uncommitted.

Powerforged Burst validation (2026-10-16): full suite 3,968/3,968 and GABot 10/10. The strict audit initially flagged missing semantic assertion labels on the zero-cell fixture; after adding them and regenerating that fixture, it passes with 3,784 complete, zero incomplete and 184 legacy contracts. Action/inline guard, three-card baseline, PHP lint and diff formatting pass. Full scenario regeneration was not run. Changes remain uncommitted.

- [x] **Genbu's Command and Suzaku's Command targeting:** both are Skills, not Spells. Custom overrides now offer Beast allies on either side without the spellshroud filter. The existing resolution protection hook handles Nightframe, not spellshroud, and remains intact. Four new friendly/opposing Shenju targeting fixtures fail against the old bodies and pass with the overrides. Actual Spells retain their spellshroud restriction.
- [x] Genbu fixtures: a spellshrouded Shenju on either side is selectable, gains +3 LIFE and lets the activating champion recover 6; the existing non-Shenju Beast fixture verifies LIFE without recovery.
- [x] Suzaku effect follow-up: pending bonuses now activate on the next attack and clear on combat exit. Five new fixtures verify no POWER bonus before/after combat, +4 Shenju/+2 ordinary Beast damage, bypassing Genbu taunt, Shenju-only prevention bypass, and unused-effect expiration. Two scope fixtures fail against the old implementation; disabling prevention bypass changes the Shenju damage from 8 to 7 and fails its assertion. The older combat snapshot now expects the consumed marker to be absent.
- [x] **Powerforged Burst:** Powercells are sacrificed before responses, and X is stored with the stack entry for resolution. The Skill can target spellshroud. Four new fixtures cover X = 0/1/3 before priority and activation without Powercells; the existing three-cell damage/draw fixture also passes. Skipping sacrifice fails the timing assertion.
- [x] **Penumbral Waltz:** preparation counters are paid before priority, with X stored on the stack entry. Three timing fixtures cover X = 0, 1, and 3; the existing Tristan fixture also passes. Omitting payment makes the timing assertion fail. Validation: full suite 3971/3971, GABot 10/10, strict semantic audit and baseline guards pass.
- [x] **Sparkling Adornment:** sheen is removed before reserve payment and responses; the paid amount travels with the stack entry. Three new fixtures cover partial/full payment and refusal without sheen; the existing transfer fixture also passes. Omitting payment fails the timing assertion. Validation: full suite 3974/3974, GABot 10/10, strict semantic audit and baseline guards pass. Changes remain uncommitted.
- [x] Add timing, legal-boundary and missing-payment fixtures for the cost changes, including zero where the printed cost permits it.

Exit: focused fixtures fail against the old implementation and pass with the fixes; the full suite, GABot and local guards pass. Keep Skills targeting and cost timing as separate reviewable batches.

### Phase B — Reconcile older findings against current code

For each item, record one of: already fixed (with evidence), confirmed defect (with reproduction), missing test, or explicit design limitation. Do not count historical notes as current bugs without checking.

- [x] Horticounter investigation: identified `vve1szpv86` (ALC Alter), with no runtime handler, alias or fixture/inventory entry. Recorded the missing implementation and the shared negate helper’s zero-payment limitation in ENGINE-FINDINGS.md. The audit led to the implementation below.
- [x] Horticounter implementation batch: X declaration and Herb sacrifice before responses, optional zero-payment response, and successful-negation banish/glimpse. Eight fixtures cover the payment matrix, protected activation, and discount-preserved X; four mutation checks detect missing/broken behavior. Full suite 3982/3982, GABot 10/10 and all local guards pass. Runtime-only implementation; no generated file edited, no full scenario regeneration. Changes remain uncommitted.
- [x] Eight of Hearts / Abnegation reconciliation (2026-10-16): both defects reproduced with loaded fixture states. Eight of Hearts refuses an affordable two-Suited-ally payment; Abnegation lacks its Ephemerate registration despite an existing controller-specific negation tracker. See ENGINE-FINDINGS.md for evidence and regression matrix.
- [x] Eight of Hearts implementation: 0–2 eligible Suited ally sacrifices pay 3 each before responses; affordability includes the sacrifice credit without double-counting reservable allies. Ten fixtures cover 0/1/2 sacrifices, cost-4 boundary, ineligible objects, insufficient combined payment and payment timing. Runtime probes also cover opposing-view eligibility and ignored costs.
- [x] Abnegation implementation: Ephemerate (2) uses Alice Bonus and the existing `ControlledActivationNegated` tracker. Six fixtures cover actual own negation, no Alice, no negation, accepted/protected negate attempts, turn reset, reserve payment and ephemeral resolution. Runtime probes check opponent-only negation and turn expiry independently of activation timing.
Validation (2026-10-16): 16 new fixtures; full suite 3998/3998, GABot 10/10, strict contracts (3814 complete, zero incomplete, 184 legacy), action/inline and three-card baseline guards pass. Mutating the sacrifice credit or using the negator tracker makes the respective positive fixture fail. PHP lint passes; semantic backlog regenerated. Full scenario regeneration was not run. Changes remain uncommitted.

- [x] Keyword reconciliation: Calculated Foresight's generated RANGED_3 grant and Reconnaissance Field's REST/Ranged/true-sight ability already work and have fixtures. Fixed Steady Sharpshooter's unintended distant grant and attack-only Ranged expiration; implemented Ritai Stablemaster's field-granted Horse Kindle in both payment and affordability. Positive/negative fixtures cover aim spending, near/distant attacks, Horse eligibility and suppressed grants. Updated the keyword audit's stale diagnoses.
- [x] Trained Birdroid: its generated ActivateAbility listener was not dispatched. The current Powercell's target continuation now applies the trigger using the known event identity, after the source token has left the field. Class-bonus positive/negative fixtures verify the buff. No generic listener timing change was made; Mercurial Heart, the other Powercell item, has no activated ability.
- [x] Ability costs: Corhazi Trapper pays preparation through the cost table before its effect target, Smoldering Cook sacrifices Food, and Geni, Gifted Mechanist pays (2), REST and graveyard banish before its effect. Geni/Gifted Mechanist is one card, not two. Missing-cost refusals, Food timing, reserve payment, Drone creation and floating-memory buff fixtures cover the changes.
- [x] Boons: Lesser Boon of Distance now uses effective Ranger classes in both prerequisite and target selection. Greater Boon of Parvati grants ready owned regalia the REST prevention ability; a fixture consumes the granted prevention against actual damage. Boundary probes cover suppression, REST and opposing controllers.
- [x] Huaji durability: not a card-data defect. Abyssal Fall is the back face of Heaven's Rise, whose printed durability is 2. The cached ruling says counters persist through transformation; a real end-phase transformation fixture verifies this without seeding counters on the back face.
- [x] Invalid Ephemerate clicks now give refusal feedback while preserving the graveyard card and payments. Embedded custom-parameter separators are a documented transport limitation: Exorcism already uses controller:index entries separated by commas; its three fixtures pass. The reviewed CUSTOM encoders expose no additional concrete instance of that old defect. Arbitrary text containing pipes still requires encoding; no shared parser change is included.
- [x] Forged Scalemail: confirmed automatic banish/prevention/draw remains. Phase B's requested choice plan is recorded in ENGINE-FINDINGS.md; implementation is transferred to Phase C because the current synchronous damage path must suspend and resume safely. This is an explicit retained limitation, not a completed optional choice.

Phase B disposition: all historical items are reconciled. Card-local fixes and the existing-correct paths have regression evidence; Forged Scalemail's damage-choice work is explicitly carried into Phase C. Follow-up validation: 4,015/4,015 fixtures, GABot 10/10, strict contracts (3,831 complete, zero incomplete, 184 legacy), action/inline guard and three-card baseline all pass. Seventeen new fixtures and two updated cost fixtures; ten mutations produce eleven expected fixture failures, and eleven boundary probes pass. PHP lint and diff formatting pass; semantic backlog regenerated. Full scenario regeneration was not run. Changes remain uncommitted.

Exit: every item has a current disposition and evidence in ENGINE-FINDINGS.md; confirmed fixes are grouped into bounded implementation batches with regression fixtures.

### Phase C — Audit and design the wider engine changes

**Audit/design exit met (2026-10-16).** This uses the phase's original exit below:
implemented fixes are distinct from documented deferred designs. It does **not**
mean all engine work is implemented. See [PHASE-C-HANDOFF.md](PHASE-C-HANDOFF.md)
for source inventories, affected paths, design decisions, unresolved rules
questions, regression plans and explicit Phase D implementation tasks.

Owner authorization: “Continue through phase c” and “Finish phase c”. No push or
PR is authorized. The dated checkpoints later in this document are history.

- [x] Exalted checks audited and remaining predicate families inventoried. Kindle, Furnace Drone, Viridian, Fire-source and Omen fixes have tested implementations. Remaining clause-specific changes, non-Norm decisions and distinct-element cases are documented deferred designs in D0; not a blanket correctness claim.
- [x] Combat-reader audit: ObjectCurrentPower and defender redirect fixes implemented. Remaining raw reads inventoried and separated into presence checks, attacker-context paths, temporary trigger context and identity follow-ups. Remaining per-card changes are deferred to D0.
- [x] Starting-champion On Enter/opening setup: sequential Enter calls and three real startup fixtures; mutation fails all three.
- [x] Graveyard Reaction availability: actual defending Twingale Ephemerate fixture; scan-gate mutation fails.
- [x] Multistrike audit/design: selected-defender model, legal declaration, retaliation, identity, damage and trigger contracts recorded. **Implementation deferred to D0.** Existing keyword/granted marker is not functional support.
- [x] Target-dependent discount audit: Slipstream, Spectral and Astral payment/category binding implemented. Forty-nine retained boundary checks now cover previews, one-shot consumption, interruption, removed targets, affordability and controller queries. Two newly found removed-entry defects fixed; both mutations fail. Slipstream's separate missing negation clause is in D0.
- [x] Eternal Magistrate audit/design: pre-removal veto plus pre-payment legality and cross-root generator checks specified. **Implementation deferred to D0.** Imbue alone does not implement the restriction.
- [x] On Enter response timing: Etui payment/decline and source identity fixed; real fixtures and mutations pass.
- [x] Forged Scalemail audit/design: resumable damage **and caller continuation** specified, with exactly-once state and tests. Unpreventable damage is an explicit rules question correcting the earlier skip-offer assumption. **Implementation deferred to D0; current prevention remains automatic.**

Exit: each audit has either a verified no-change disposition, a tested implementation, or a documented deferred design decision. Generated files remain untouched by hand.

Validation: 4,071/4,071 integration fixtures, GABot 10/10, 49 loaded-state boundary
checks, strict contracts and all local guards pass. Full scenario regeneration
remains Phase G. Changes remain uncommitted and unpushed.

### Phase D — Resolve partial clauses and coverage exceptions

Implement the deferred Phase C contracts before adding card-local approximations.
The audit/design phase is closed; all implementation tasks here remain open until
the corresponding gameplay fixtures and mutation checks exist.

Element continuation follow-up (2026-10-16): Coiled Fatestone now counts Fire membership on the discarded object and chooses only live hand slots for each random discard. Parcenet's reveal checks effective Wind membership. Six retained positive/negative boundary checks bring the Phase D total to 150; both element regressions are mutation-detected. The existing Coiled gameplay fixture passes with its corrected second-discard snapshot. Full verification passes: 4,103/4,103 integration fixtures, GABot 10/10, 49 Phase C and 150 Phase D boundaries, strict contracts (3,919 complete, zero incomplete, 184 legacy), action/inline guards and zero-card coverage baseline. Full scenario regeneration was not run. Wildgrowth Fatestone, Blazing Cindercharge, Slime Eruption and lineage-specific Nico checks remain to reconcile.

Element continuation follow-up (2026-10-16): Blazing Cindercharge now selects effective Fire Aethercharges using membership checks (also removing a string-to-`in_array` error). Slime Eruption uses effective Ally/Slime identity and Fire membership both when offering banishment and counting its two non-Fire allowance. Eight retained boundary checks cover ordinary Fire, Exalted Fire, non-Fire exclusion at the cap, and unchanged non-Fire accounting after Fire banishment; both selection regressions are mutation-detected. Full verification passes: 4,103/4,103 integration fixtures, GABot 10/10, 49 Phase C and 158 Phase D boundaries, and all guard rails. Full scenario regeneration was not run. Wildgrowth Fatestone and lineage-specific Nico reconciliation remain open.

Element continuation follow-up (2026-10-16): Wildgrowth Fatestone uses effective Wind membership for the entering object. Nico, Rapture's Embrace uses printed Water membership for lineage subcard IDs, excluding the current champion as before; lineage has no live object on which to query field overrides. Thirty-six retained checks cover both owners and viewing perspectives, Wildgrowth suppression, Exalted/ordinary Wind, non-Wind entry, Exalted Water lineage, non-Water lineage and empty lineage. Both membership regressions are mutation-detected. Full verification passes: 4,103/4,103 integration fixtures, GABot 10/10, 49 Phase C and 194 Phase D boundaries, and all guard rails. Full scenario regeneration was not run. These boundary checks supplement existing gameplay coverage; the wider element inventory remains open.

Wind reader follow-up (2026-10-16): Enhance Hearing now accepts effective Wind membership (including Exalted Wind) and uses effective Reaction subtype membership. Gearstride Academy checks the entering object’s effective Ally type and Wind membership. Fifteen retained boundary checks cover ordinary/Exalted Wind, non-Wind rejection, imbue and suppression; restoring either exact-equality check makes the suite fail. Full verification passes: 4,104/4,104 integration fixtures, GABot 10/10, 49 Phase C and 409 Phase D boundaries, strict semantic contracts and all coverage/action guards. PHP lint and diff whitespace checks pass. The wider D0 inventories and authored-source-dependent mechanics remain open. Changes remain uncommitted and unpushed.

Combat prerequisite follow-up (2026-10-16): Meteoric Volley and Overpowering Defense now resolve the combat attacker in the current viewer’s frame and use its recorded UID when available. The shared maintained helper rejects departed participants and checks the actual controller and effective unit properties; older queues without a positive UID retain coordinate support. The generated Overpowering Defense prerequisite is superseded by a maintained override. One hundred eighty retained checks cover both players/viewers, champion/ally/non-unit identity, effective Guardian class, compaction, departure and controller changes. Both perspective and identity mutations fail. Full verification passes: 4,104/4,104 fixtures, GABot 10/10, 49 Phase C and 589 Phase D boundaries, and all guards. The remaining generated-reader audit and authored-source-dependent mechanics stay open. Changes remain uncommitted and unpushed.

Generated element-equality batch (2026-10-17): eighteen generated bodies that compared `CardElement()` with `===`/`!==` now use `GAIsElement()` membership via maintained overrides (the generated bodies with only those comparisons changed): Banish (96659ytyj2), Windstream Mutt, Polkhawk lineage, Equestrian, wc8tuhuy4x, Vanitas, Drowned Exorcist (an Exalted Water card is no longer banished), Force Load, KEhmWGivJp, 7xgwve1d47, Windpiercer, Diana's wind count, Polkhawk Bonus, Link VelTech, qmyn2rz308, Wind Cutter, THjSE7caau and r7oifozaog. `test-ga-phase-d-element-batch.php` (31 checks, in `ga-verify-all.sh`) proves every override is the active handler and gameplay-checks wc8tuhuy4x, Drowned Exorcist and KEhmWGivJp with Exalted Fire/Wind/Water; reverting `GAIsElement` to equality fails it. The other fifteen overrides are mechanical and covered by the active-handler check only, not by behaviour tests. Left for separate rules review: the distinct-element graveyard counts (f0ymeslfpw, GameLogic `$distinctElements`), the element-requirement cost check (alnjt7DyZL) and the 49 "value/local" inventory rows. Full suite 4,104/4,104, GABot 10/10, all guards pass. Uncommitted.

#### D0 — Deferred engine implementations from Phase C

- [ ] Remaining basic-element membership/effective-property batches in the element inventory: costs and continuations, damage/discard sources, field entry/passives, domains and generated handlers. Separate Norm membership, Norm-only, advanced classification and distinct-element semantics; see handoff C1.
- [ ] Remaining combat event/reader fixes: delayed choice identities, defensive generated handlers and Possessed Reaping's exact killed-card destination. Preserve correct attacker-context reads; see handoff C2.
- [x] Eternal Magistrate: schema/generator departure veto and pre-payment gates cover normal/ignored-cost materialization, effect-driven level-up, preserved return, material activations and banishment costs. The route audit found no direct material-array removal bypass in maintained/generated card bodies. Generic `Remove` and `MZMove` gates, stale selections, ownership/viewer combinations, suppression and the unchanged unconfigured-root generator output are tested; see handoff C3.
- [ ] Forged Scalemail: resolve exact unpreventable replacement behavior, implement resumable damage and caller continuations, then accept/decline/save-reload/exactly-once fixtures; see handoff C5.
- [ ] Multistrike: declared distinct defenders, per-defender retaliation, damage and trigger context; implement on the resumable damage model, not as repeated Cleave; see handoff C4.
- [ ] Slipstream Vault: implement and test the separate “negate all activations targeting it” clause, including responses and both controllers. The completed discount work covers Become Distant and payment binding only.

Phase D started after checkpoint commit `1d7a792f0`. First element batch: Molten Arrow’s sequential graveyard cost now uses Fire membership, accepting Exalted Fire at each continuation. Three gameplay fixtures cover all-dual, mixed and insufficient eligible payments; the all-dual fixture failed before the fix. The wider element task remains open.

Runtime identity/element follow-up (2026-10-16): Possessed Reaping captures the actual killed card's graveyard arrival in its queued On Kill context. A live-ordinal registry and Graveyard `BeforeRemove` hook preserve the reference through cleanup/save/load and invalidate it on departure, without a CardID fallback. Stolen dying objects go to their owner's destination; the On Death context still belongs to the last controller. A new duplicate-graveyard gameplay fixture and mirrored owner/viewer boundaries exercise the path. Malignant Athame binds its delayed damage to the hit champion's UID and source CardID; Blastshot Pump carries its damage/source in the continuation. Inner Court Schemer and Scorchfire Assassin bind the preparation payer and attacking recipient before their first choice, refusing payment if either leaves. Existing gameplay fixtures pass. Misteye Archer, Captain Archer, Fractal of Refreshment, Intrepid Spearman and Relic of Dancing Embers now use effective basic-element membership; all five changes are mutation-detected. Full verification: 4,104/4,104 integration fixtures, GABot 10/10, 49 Phase C and 394 Phase D boundaries, strict contracts (3,920 complete, zero incomplete, 184 legacy), all guards and zero uncovered cards. Full scenario regeneration was not run. Changes remain uncommitted.

**Authored-source dependency (user requested this be noted):** The local Grand Archive card database/card-editor connection is unavailable. Schema regeneration works, but its log explicitly reports that macro code could not be generated; the cached macro file is preserved. No current authored-row export is available. Do not reconstruct canonical await source from lowered PHP or call database reconciliation complete. The full damage-caller migration for Forged Scalemail/Multistrike and the target-declaration migration for Slipstream Vault require the current authored rows and regenerated-path verification. These mechanics are still unimplemented; noting the source dependency does not close D0. Remaining generated element/defensive-reader audits and distinct-element semantics also stay open. See the [DB handoff](../card-db-patches/ga-phase-d-required.md).

#### D1 — Partial clauses and coverage exceptions

- [x] Greater Boon of Luxera: enforce the chosen-name generation prohibition.
- [x] Veiled Dash: choose any amount of wind cards to reveal.
- [x] Heavenly Guide: track whether the relevant lineage card was played.
- [x] Greater Boon of Enki: handle simultaneous token creation correctly.
- [x] Frostbitten Etui and Stifling Aethercharge: real On Enter negation fixtures now exist, including Etui payment/decline. Removed obsolete semantic exceptions and reduced the baseline to two cards.
- [x] Duplicitous Replication: resolve the activation-window limitation and detect qualifying regalia entries beyond materialization.
- [x] Lorraine, Ascendant Wings: decide scope for the 2024-cost/open-ended Sword-generation ability before implementation.

Exit: update UNFIXABLE.md and the baseline only when the relevant fixtures exist; any retained exception has an explicit reason. The Phase D fixtures remove the last two exceptions; the baseline is now empty.

### Phase E — Expand rules-clause coverage

These are recorded unexercised paths, not confirmed bugs. Recheck existing fixtures before adding duplicates. Implement fixes discovered through the normal regression workflow.

| Batch | Paths to verify |
| --- | --- |
| Wind | Sleety Retreat/Tinderflare Pivot Ranger-champion targets; Tempestuous Seraphim imbued On Enter; Diablerie relic theft; Stifling Gyre pay-or-negate. |
| Norm | Crystallized Destiny prevention; Suited Trickery/Plea for Peace restrictions; Allowance Race second Horse; Equivalent Exchange banish-two; Dodge Roll non-combat prevention; Reliable Blade spellshroud; Trivariate Dream. |
| Arcane | Staves X Ultra banishment end-phase effect; Harbinger of Lightning/Seiryuu's Command element waivers. |
| Luxem | Lumen Borealis Animal-death trigger; Uther/Shenju element waivers. |
| Crux | Vorpal Sword Merlin abilities; Merlin, Kingslayer attack bonus; Majestic Spirit half-damage rounding. |
| Exia | Annihilation damage-35+ Fast Activation. |
| Neos | Hector imbued On Enter; Tonoris token-weapon sacrifice buff; Iron Halo/Tor/Obelisk with several domains. |
| Tera | Hymn of Gaia's Grace retarget; Bloom: Autumn's Fall discard; Lu Bu Diao Chan death replacement; Flourishing Restoration element waiver. |
| Astra | Moonveil Android negate buff; Stellaria Shower starcalled branch; Stargazer's Portent copy; Scry the Stars starcalling; Cometary Vantage Aethercharge; Vanishing Eclipse aethercalling; Stellarion Shift graveyard ability; Prima Materia Brew; Astarte Fast Activation; Cataleptic Constellation wake-up lock; Constellatory Spire negate trigger; Reflect the Skies random order. |
| Umbra | Tristan level-up draw; Gloamspire Mantle ambush; Gloamspire Wraith distant On Hit recovery; Nightmare Coil positive path; Death Essence Amulet hand/memory choice; Steel Slug memory payment; Alice-bonus graveyard Ephemerate; Spirelle negate; Soutirer Vortex omen trigger; Mary Ann keyword sharing. |
| Exalted | Labyrinth stealth; Mirrorbound opposing influence cap; Aenean Repudiation Progression cost. |
| Water | Frozen Divinity opposing level-2 champion clause; Treacle/Weiss Bishop stealth with ephemeral. |

Exit per batch: each clause has observable, non-vacuous evidence or a documented engine blocker; regenerate the backlog after changes.

### Phase F — Fixture quality and automation

- [ ] Upgrade the 184 legacy snapshots opportunistically; resolve the 11 remaining legacy lint flags as their fixtures are understood.
- [ ] Move assertions from fixture files into scenario definitions when touched; prove regeneration equivalence.
- [ ] Decide whether to promote the owner's untracked smoke probe into a maintained tool. Do not modify the owner's scripts as part of unrelated work.
- [ ] Decide whether to generate the limited printed-cost and vanilla/keyword fixture families automatically.
- [ ] Choose and configure CI for the suite, GABot, scenario regeneration, strict semantic audit and existing guards.
- [ ] Decide where quarterly drift checks run and configure an authorized schedule.

Exit: adopted automation is runnable and verified; declined/deferred proposals are explicit. Legacy migration remains opportunistic unless its scope is changed.

### Phase G — Review and branch closeout

- [ ] Reconcile this roadmap, ENGINE-FINDINGS.md, UNFIXABLE.md and the generated backlog; no stale claims about Skills being blocked by spellshroud.
- [ ] Review the branch diff and any shared-engine/generator impact.
- [ ] Run full verification including scenario regeneration (`ga-verify-all.sh --full`), GABot and all guards; investigate drift rather than blindly accepting snapshots.
- [ ] Record remaining deliberate exceptions and decisions; agree that roadmap closeout is reached.
- [ ] Push and open a PR only when authorized. Neither has happened.

Recommended order: A targeting fixes, A cost timing, B reconciliation, then explicit C/D implementation batches (including the transferred Scalemail choice) and E coverage batches. F quality work runs alongside touched files; tooling choices are separate. G follows completion or explicit deferral of the preceding work.

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
- **Legacy fixtures (184 at the Ardus verification; originally 182)**. OPEN. 178 are hand-maintained snapshot fixtures (generated from the
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
  - Abnegation's conditional Ephemerate is missing. Phase B audit corrected the original diagnosis: the controller-specific negation tracker already exists; the conditional registry entry is absent.
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

### Phase 3 -- complete (2026-10-16)
- Every one of the 2,477 implemented cards now has a fixture; 3 cards have no *semantic* fixture and are listed in `UNFIXABLE.md` with the reason (Frostbitten Etui, Duplicitous Replication, Lorraine, Ascendant Wings), together with the clauses of other cards that no fixture can reach. The 21 nameless backlog rows were uuid aliases of fixtured cards; `build-ga-semantic-backlog.php` now resolves them.
- The last batches implemented cards that had no engine support at all (the Norm boons Nuwa, Isis, Dux, Horses, Enki, Inari, Knox, Detachment and Luxera, Greater Boon of Astraeus, Gate of Alterity, Prismatic Spirit) and fixed the generated Auspicious Manifestation, Fervent Lancer, Hemorrhaged Intimidation and Dusklight Communion; see the last entries of `ENGINE-FINDINGS.md`.
- Remaining roadmap items: the Phase 4 probe integration (`_smoke_probe.php` into `DevTools/probes/`) and the Phase 5 guard rails (generator rejecting inline definitions, CI, quarterly drift check).
- Phase 5 guard rails are in: `DevTools/ga-verify-all.sh` (suite, GABot, strict semantic audit, truncation + inline-definition audit, uncovered-card baseline; `--full` adds `verify-ga-scenarios.php`), the generator refusing inline definitions and unmet `rejects()` steps, and `uncovered-baseline.txt`. There is no CI service configured in this repository; the script is the entry point for one. The quarterly drift check is the suite itself (`audit-fixture-drift.php --test=<slug>` explains a single drifting fixture).
- Phase 4: `ga-scaffold.php` exists; the probe integration is not done: `DevTools/_smoke_probe.php` is an untracked, never-committed local tool, so promoting it into `DevTools/probes/` is left to the owner.
- Closing the strict audit found five dead scenarios (steps that silently did nothing: Burnished Obelith, Seafletched Serpent, Total Whiteout, Conjuring Fluorescence, Nia) and Field of Ranks and Files tested with a card that could not be played; all are repaired. 

Phase C defender follow-up: corrected Samaritan’s Reach’s false Crowd’s Favor and
Hymn of Gaia’s Grace’s target lookup, stored perspective and target markers.
Added three real-attack fixtures, including declining the optional redirect.
The broader combat-reader audit and remaining Phase C mechanics stay open.

Phase C omen follow-up: GetOmenCountByElement now accepts each printed element
of dual-element omens. Eight fixtures cover Favorable Omens, Chrysalis and
Inverted Pyroslash, with three failing controlled mutations. Full verification:
4,061/4,061 integration, GABot 10/10, 3,877 complete semantic contracts, zero
incomplete, 184 legacy, 8,870 semantic assertions; all local guards pass.
Target-dependent discounts are the next implementation batch. Wider element and
combat audits, Eternal Magistrate, Multistrike and Forged Scalemail remain open.

Phase C Slipstream follow-up: declared target identity now determines the class
reduction before reserve payment and is preserved through resolution. Four new
fixtures plus the updated existing fixture pass. Restoring the unconditional
discount fails the ordinary-target cost assertion. Full verification passes
4,065/4,065 integration, GABot 10/10, 3,881 complete semantic contracts,
zero incomplete, 184 legacy, 8,881 assertions; all local guards pass.
Astral Seal and Spectral Haunting remain in the target-dependent cost batch.


### Spectral Haunting payment/category checkpoint

Alice Bonus now requires a pre-payment commitment to Specter targets to receive
its four-reserve discount. The stack entry retains that category and resolution
only offers live Specter allies. Declining pays six and can return any ally.
Two mixed-graveyard fixtures assert both costs and the offered target lists;
both cost and chooser mutations fail. The older recorded Spectral Haunting test
now answers the declaration before payment, retaining its previous expectations.
Verification: **4,067/4,067**, GABot **10/10**, **3,883 complete / 0 incomplete / 184
legacy**, all local guards, PHP lint and diff formatting pass. Full scenario
regeneration remains Phase G. Astral Seal, wider reader/element audits, Eternal
Magistrate, Multistrike and Forged Scalemail remain Phase C work; interruption and
insufficient-reserve depth for this category flow also remains to be added.


### Astral Seal payment/category checkpoint

Claiming the Cleric discount now commits to activations whose names occur in live
banishment before payment. Declining pays three and retains the unrestricted
chooser. The activation preserves the qualifying names; its on-play trigger
serializes the declaration in its own context. Resolution offers and revalidates
legal activations in that paid category. Removed banishment entries cannot grant
a new discount; a later banishment change does not undo the paid category.

Four fixtures cover the discounted and full-cost branches, opposing banishment,
and no Class Bonus. Cost and target-filter mutations both fail. An isolated
runtime probe also checks opposing activation controllers, removed banishment,
preserved declared names, unnegatable targets and removed targets. These probe
checks are not additional retained gameplay fixtures.

Validation: 4,071/4,071 integration, GABot 10/10, 3,887 complete / zero incomplete /
184 legacy, 8,901 semantic assertions; all local guards, PHP lint and diff check
pass. Phase C remains open: wider element/combat reader audits, Eternal
Magistrate, Multistrike, Forged Scalemail and further target-cost interruption,
affordability and dry-run fixtures. Full scenario regeneration remains Phase G.
Changes remain uncommitted and unpushed.

Phase C audit/design closeout: see PHASE-C-HANDOFF.md. Forty-nine retained
boundary checks found and now prevent removed-target discounts in Slipstream
Vault and Spectral Haunting. Both mutations fail. Full suite 4071/4071, GABot
10/10 and all guards pass. Five remaining engine design families and Slipstream's
separate negation clause are explicit unchecked D0 implementation work; no
claim that Multistrike, Magistrate or optional Scalemail is implemented.

Phase D first-batch verification: 4,074/4,074 integration fixtures, 49 retained boundary checks, GABot 10/10, strict semantic audit and the two-card baseline guard pass. PHP lint and diff checks pass. This batch remains uncommitted after the Phase C checkpoint.

### Phase D implementation progress

Database follow-up: [Phase D database handoff](../card-db-patches/ga-phase-d-required.md)
lists the required authored-row reconciliation and regeneration checks. Current
behavior uses maintained overrides; no live database migration is confirmed.

D1 clauses are implemented and fixtured: Luxera generation, Veiled Dash choices, Heavenly Guide played provenance, Enki simultaneous token choices, Duplicitous Replication field-entry events and Lorraine's full printed ability (explicitly authorized by the user). The 2024-reserve cost is paid in real gameplay fixtures; it is not discounted for testing. Lorraine remains inherited in lineage and Sword generation has no chosen-count ceiling. Both previous semantic coverage exceptions are now covered.

D0 progress includes Molten Arrow/Creative Shock/Firetuned Fire membership, Dilu/Jueying effective element checks, activation element filters and Eternal Magistrate's schema departure veto plus pre-payment gates. The remaining D0 items are still open. No Phase D completion is claimed until its engine work is finished.
