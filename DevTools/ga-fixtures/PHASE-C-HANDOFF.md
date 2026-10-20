# Phase C audit and design handoff

2026-10-16. Phase C is closed **as the audit/design phase defined in ROADMAP.md**.
This does not mean all audited mechanics are implemented. The roadmap's stated
exit permits a tested implementation, verified no-change disposition, or a
documented deferred design decision. The deferred work below is carried into
Phase D with unchecked implementation tasks; no card-completeness claim changes.

## Disposition

| Area | Phase C result | Implementation status |
| --- | --- | --- |
| Exalted element checks | Earlier Kindle/Furnace/Viridian/Fire/Omen fixes tested; remaining source inventory and predicate contracts recorded below | Remaining membership, effective-property and distinct-element work deferred to D0 |
| Defender combat readers | Earlier power/redirect fixes tested; remaining raw-reference inventory and context contracts recorded below | Remaining trigger/identity work deferred to D0 |
| Starting champions | Explicit sequential opening Enter dispatch tested | Implemented |
| Graveyard Reactions | Real defending Twingale payment/prevention/banishment regression | Verified existing path |
| Target-dependent discounts | Slipstream, Spectral and Astral declarations implemented; interruption/dry-run boundaries retained; removed-entry defects fixed | Audited payment binding complete; Slipstream's separate negation clause remains D0 |
| On Enter response timing | Etui payment/decline and source-identity fixes; real response fixtures | Implemented |
| Eternal Magistrate | Movement veto and payment contract designed below | Unimplemented; deferred to D0 |
| Multistrike | Selected-defender combat model and integration/test plan below | Unimplemented; deferred to D0 |
| Forged Scalemail | Damage continuation and caller-resumption contract below | Optional choice unimplemented; deferred to D0 |

## Reproducible source inventory

Run `python3 DevTools/audit-ga-phase-c.py`. It reads every `Custom/*.php` file
and GeneratedMacroCode.php and writes these navigation inventories:

- [Element accesses](phase-c-element-inventory.tsv): 158 sites.
- [Raw combat participant reads](phase-c-combat-inventory.tsv): 144 sites.
- [Material-deck references](phase-c-material-inventory.tsv): 236 sites.

These are source-site counts, **not live defect counts or coverage proofs**.
Comments are excluded. The nearest declaration is a textual navigation hint,
not a PHP parse tree. The review bucket is not a verdict. Element locals can be
compared downstream; material variables can be passed to a later movement
helper. Generated bodies can be replaced or wrapped by maintained handlers, so
the same key being assigned elsewhere does not prove a body is dead. Before
implementing an individual row, trace its callers, continuations and final
runtime registration. Rebuild the inventories after edits, since line numbers
are snapshots. No generated file was edited by hand.

## C1: Remaining element decisions

Decision: retain clause-specific implementation batches rather than replace all
element equality operators. The dictionary's four dual-element combinations
remain EXALTED with FIRE/WATER/WIND/NORM. The inventory now also includes
CardDQHandlers, CardLogic, MaterializeLogic and OpportunityLogic, which a search
limited to GameLogic/CombatLogic/overrides misses.

| Predicate | Implementation contract and checks |
| --- | --- |
| “Fire/Water/Wind element” | Membership, using effective properties for live field objects. Test ordinary, dual, wrong element, removed and effective override; pair affordability, initial chooser and continuation revalidation. |
| “Non-Norm element” | Do not equate “has an element other than Norm” with “does not have Norm.” For a clause excluding Norm cards, use absence of Norm membership; for a clause requiring Norm-only, use IsNormOnlyElementProperty. Add EXALTED,NORM versus ordinary Norm and a non-Norm control before selecting a predicate for a particular card. |
| “Non-advanced” | Use the advanced-element classification, not “has any basic element.” Exalted Fire/Norm remain advanced. The retained boundary checks establish this distinction. |
| Enabled elements and requirements | Preserve CanPlayerUseCardElement/GetPlayerEnabledElements and multi-element requirement semantics. Membership fixes must not let Exalted cards bypass Exalted requirements. |
| Distinct elements | **Decided (2026-10-20): each card in the selection needs its own element that no other selected card is counted for (system of distinct representatives); multi-element cards are allowed. Exalted counts as an element.** Implemented for Slime King (`GASlimeKingEligibleGraveyard`, `GADistinctElementSelectionPossible`; `DevTools/test-ga-slime-king-elements.php`). Other cards with distinct-element wording have not been re-audited against this rule.

Concrete remaining batches from the source review:

1. CardDQHandlers Fire costs/triggers; additional discard-cost preview and
   execution; OnDiscardCard and AeneanGutteringFlamesBonus; Frostbite Water
   vulnerability and Everflame Fire-source checks. Match source identity and
   effective versus printed properties to each clause before changing.
2. Field-entry and selected-field predicates in GameLogic/CardLogic, including
   Wind/Water comparisons in passives and triggered helpers. Use effective field
   queries; test suppression and controller, not only a dual printed card.
3. DomainRevealMemoryUpkeep's allowed-element array comparison, plus Dawn's cost
   surcharge and Shangxiang's prevention. Test EXALTED,NORM explicitly. Memory
   overrides require a separate effective-property decision; do not blindly use
   the field-object rule for a printed-card cost check.
4. Generated basic-element comparisons and local element variables, including
   On Attack/On Hit bodies. Author through the card editor or maintained final
   overrides, and inspect whether current wrappers still delegate to the body.
5. OpportunityLogic activation element filters and advanced-only exact checks.
   ScavengeForElement currently has ARCANE and EXIA callers: no printed dual
   ARCANE/EXIA card in this dictionary proves a present Exalted regression there.
   Its generalized equality remains a future compatibility concern; test the
   actual caller before changing it.

Deferred reason: these predicates have different meanings and source lifetimes.
A bulk equality-to-membership patch would conflate requirements, distinctness,
advanced classification and effective overrides. D0 owns the remaining clauses
and their fixtures; this audit does not label every inventory row a bug.

## C2: Remaining combat-reader decisions

Decision: preserve attacker-relative stored combat coordinates and normalize at
the boundary where a reader uses the current viewer's zone. Do not change the
stored format or mechanically replace every raw read.

| Reader family | Disposition |
| --- | --- |
| Presence-only reads in damage/combat gates | No coordinate conversion is needed when only testing whether combat exists. Their combat/non-combat classification is a separate concern. |
| CombatDealDamage/CleaveDealDamage and declaration handlers | Read in the attacker context or explicitly switch to it. A raw read alone does not prove a perspective bug. Preserve the switch/restore pairing on every early return. |
| GetRetaliatorOptions, ChillToTheBone, intercept/redirect conversions | Already establish a perspective explicitly. New redirects must also update stable target state and the target marker, as the earlier Hymn/Samaritan fixes demonstrate. |
| ON_HIT generated and maintained handlers | QueueHitTriggeredAbility captures participants; OpportunityLogic restores them while resolving the trigger. That context is temporary. Choices answered later must capture the necessary identity/controller rather than reread a now-cleared CombatTarget. Retaliation and multiple hits require source/recipient identity per event. |
| InnerCourtSchemerRemovePrep/ScorchfireRemovePrep | On Attack source context is normally attacker-owned, but the delayed answer's identity lifetime needs its own regression; do not claim the raw read is independently broken. |
| MalignantAthame/BlastshotPump | Normally attacker-side On Hit readers. Retain source/controller and hit recipient across interactive continuations; prove mirrored field indices and weapon destruction/compaction. |
| PossessedReapingOnKill | Needs the killed card's destination/owner identity, not a surviving field slot or a CardID-only fallback among duplicate graveyard cards. QueueKillTriggeredAbility currently captures card/power/HP, not a stable destination identity. This is a specific follow-up design, not fixed by converting CombatTarget alone. |
| Remaining defensive generated bodies | Inspect final override registration and retained delegation before replacing. Test the actual defending controller and distinct cards at mirrored indices. |

The four attacker/viewer combinations are now retained in the boundary suite.
They verify the normalization helpers, not every card reader. D0 must migrate
only proven defects, with per-handler gameplay fixtures and mutations. This
preserves correct attacker-context readers while expanding stable event context.

## C3: Eternal Magistrate movement contract

Current gap: Imbue and its IMBUED marker exist, but no material-deck departure
restriction does. A destination BeforeAdd hook is too late: generated MZMove
marks the source removed before adding the destination. Direct Remove callers
also need to respect a departure restriction.

Design decision: add an opt-in schema/generator departure-veto hook before any
source mutation. The hook receives source owner/zone and movement context;
failure leaves both zones unchanged. MZMove must stop when removal is refused.
Do not merely decline the destination add, or the source card disappears. Roots
without the hook keep their existing behavior. Any direct material array
mutation must either call the guarded operation or be proven setup/cleanup.

The Grand Archive hook blocks an opponent's material card leaving while a live,
unsuppressed, imbued Magistrate is present, except during that card owner's own
materialize phase. Checking only the phase name or only the acting player is
insufficient. Special setup actions need their own explicit bypass; the current
startup already places starting champions before turns. The official
[starting-game rule change](https://github.com/weebsoftheshore/gitbook-rules)
also prevents treating the historical Spirit-entry interaction as current turn-one behavior.

Before reserve/memory/additional costs, the same legality predicate must remove
illegal activation/materialization options and refuse forced stale decisions.
Revalidate at the actual departure. A refusal cannot leave a paid cost attached
to an activation that never began. A disappearing Magistrate can make a later
attempt legal, but must not silently replay a refused action.

Affected paths: schema Material; generator Remove/MZMove output;
MaterializeChoice/MATERIALIZE including NOCOST/CONTINUE; main-phase material
activation routes in FSM/ActivateBanishedCard/DoActivateCard; preserved returns;
generated and maintained effects moving material cards to hand, memory, field,
banishment or another zone. The material inventory includes incoming references
and queries as well as outgoing moves; audit dynamic source variables too.

Required fixtures: both owners/controllers; owner's materialize phase allowed,
other player's phase and main phase blocked; non-imbued/suppressed/removed
Magistrate; multiple copies; effect move versus activation versus materialization;
ignored cost, preserved return and level-up; stale queued selection; no partial
payment, duplication, destination trigger or source loss. Generator tests must
prove an unconfigured root is unchanged and a veto prevents both Remove and
MZMove. Mutate the pre-removal and pre-payment gates independently.

Deferred reason: a general movement hook changes generated infrastructure and
requires cross-root verification. A materialize-menu-only patch cannot implement
the printed restriction. No generator change is included in this handoff.

## C4: Multistrike combat contract

Current gap: Crosswind Cuts has printed Multistrike; Windstrike Soldier grants
MULTISTRIKE_2, but combat never consumes it. Cleave enumerates opposing defenders
at damage time and ignores Stealth as a targeting exclusion; it is not a safe
substitute for individually selected attack targets.

The official [keyword definition](https://raw.githubusercontent.com/weebsoftheshore/gitbook-rules/main/glossary/keywords-and-abilities.md)
allows up to N additional targets during declaration. The official
[release notes](https://www.gatcg.com/article/asphodel-paradise-release-notes)
require legal additional targets, prioritize Taunt, and explain Cleave interaction.
In the current two-player engine, Cleave already covers the opponent's defending
objects, so Multistrike must not add duplicate defenders to that set.

Design decision: introduce one serialized combat record containing attacker
identity, weapon/intent identity, ordered distinct defender identities and each
defender's retaliation choice. Keep scalar primary-target variables only as a
compatibility view of the participant currently being processed. Freeze the
selected set at declaration; never expand it when a new enemy enters.

Declaration order: primary legal target, optional additional targets (zero is
valid), validate the complete selection including Taunt/range/Stealth/True Sight,
then mark all defenders and fire On Attack once. Resolve removed/illegal targets
by identity without substituting a different card at the same index. Redirect
changes one defender entry, revalidates legality and prohibits duplicates.

Damage must preserve the simultaneity contract while producing per-source/
recipient hit and kill records. Damage/retaliation planning cannot allow an
earlier On Hit choice to alter a later defender's scheduled damage. Decrement a
wielded weapon's durability once for the attack. Queue each hit/kill with its
own immutable context. The official [damage-step rules](https://rules.gatcg.com/game-mechanics/game-mechanics-turn-order/turn-order-combat-phase/combat-phase-damage-step)
also require retaliation ordering and replacement handling; a loop that resolves
one complete combat before starting the next is not equivalent.

Required fixtures: Multistrike 1 and granted 2; choose zero/one/two extras;
duplicates, illegal targets, Taunt, Stealth/True Sight and distance; both players;
selected target removal and unrelated field compaction; redirect/intercept;
several retaliation choices and lethal order; weapon durability once; On Attack
once, per-recipient On Hit/On Kill, zero/prevented damage; Cleave interaction;
an enemy entering after declaration is excluded. Mutate target deduplication,
stable identity, per-hit context and durability accounting independently.

Deferred reason: combat currently has one primary participant pair and separate
Cleave paths. This requires a participant/event model, not a card-local loop.
Implement after the damage continuation contract below, so prevention choices
cannot advance or duplicate a multi-defender combat.

## C5: Forged Scalemail damage continuation contract

Current gap: DealDamage automatically banishes linked Scalemail, prevents two
and draws. The positive snapshot proves that path only. The optional choice
remains unimplemented.

Design decision: make damage an explicitly resumable event, with a serialized
event ID, source/recipient identity and last-known source characteristics,
remaining/original amount, combat classification, applied replacement IDs,
pending prevention choice, and a continuation for the caller. Store scalars and
arrays only. Resolving YES/NO must consume the pending token before resuming;
a second answer is a no-op. Recompute live field objects after the choice.

Crucially, suspending DealDamage alone is insufficient. Its synchronous callers
currently proceed to damage tracking, retaliation, durability, hit/kill triggers
or the next recipient. Those callers must wait too. The event runner reports
pending/completed and owns exactly-once continuation of the caller's next stage.
It must not run a full damage function again and reapply prior modifiers.

For YES, revalidate the same live linked unsuppressed armor; banish it, apply the
permitted prevention and draw once as the replacement requires. NO preserves
the armor. Missing armor cannot banish a replacement at the old index. Missing
recipient terminates the recipient event without damaging a replacement card.
Multiple armor/prevention objects need a deterministic pending-event sequence,
with replacement ordering chosen by the affected controller where required.
Source movement must not lose its damage characteristics or controller.

Correction to the earlier Phase B plan: do not assume unpreventable damage means
the entire replacement can be skipped. The official [prevention rules](https://rules.gatcg.com/game-mechanics/game-mechanics-damage-prevention)
distinguish shielding from instance prevention and allow an attempt against
unpreventable damage. The official [replacement rules](https://rules.gatcg.com/game-mechanics/game-mechanics-types-of-effects/types-of-effects-replacement-effects.md)
also govern once-per-event application and ordering. Validate the card's exact
current text/ruling for banish/draw on that attempt before implementing this
branch. This is an explicit unresolved rules decision; no no-prompt behavior is
claimed correct by this handoff.

Required fixtures: accepted/declined combat and non-combat damage; both players;
one damage/partial prevention; exact unpreventable branch after rules resolution;
suppressed, missing or already-consumed armor; field compaction; recipient/source
leaving; several linked prevention objects; repeated/stale answer; save/reload
while pending; damage/hit/kill/draw/durability exactly once. Mutation checks must
catch automatic acceptance, damage before the answer, reapplied modifiers,
double resume and the caller advancing during suspension.

Deferred reason: all callers need a resumption contract and the unpreventable
branch needs exact card-text verification. A queued YESNO followed by ordinary
synchronous damage would give a misleading fixture while leaving combat wrong.

## Payment audit evidence and verification

`php DevTools/test-ga-phase-c-boundaries.php` retains 49 loaded-state checks.
It verifies repeated previews do not mutate zones, declarations, queues or
trackers; one-shot surcharges survive previews and are consumed once on actual
payment; actual declared categories determine costs; removed targets cannot
grant fresh discounts; missing declared recipients do not redirect; qualifying
names remain committed for Astral after banishment changes; unnegatable/removed
activations are excluded; ignored cost and insufficient reserve boundaries;
opposite-view queries and all four combat normalization combinations.

The checks found removed-entry bugs in Slipstream and Spectral, now fixed by
card-specific live target queries used during declaration, discount calculation
and resolution. Removing either helper's removed-entry filter makes the retained
suite fail. These tests supplement the existing real payment/response fixtures;
they are not additional integration scenarios and do not change suite counts.

The boundary suite is part of ga-verify-all.sh. Full integration 4,071/4,071;
GABot 10/10; strict semantic contracts 3,887 complete / zero incomplete / 184
legacy, 8,901 assertions; action guard 3,852 scenarios / zero truncated; baseline
two cards. PHP lint passes. Full scenario regeneration remains Phase G.
All changes remain uncommitted and unpushed.
