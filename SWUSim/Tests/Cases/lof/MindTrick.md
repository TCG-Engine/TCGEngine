# CombinedPowerExhaust
#// LOF_202 Mind Trick — Exhaust any number of units with a combined power of 4 or less. SOR_059 (power 1)
#// and SOR_063 (power 2) total 3 ≤ 4, so both are exhausted — taken together in ONE weighted
#// multi-select (a single modal with a live "N of 4 power left" counter) and resolved by one Confirm.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_202}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED

---

# SourceBlanked_SelfAndAllyLoseRaid
#// LOF_202 Mind Trick — "those units lose all abilities … for this phase." A blanked unit loses its OWN
#// printed Raid AND stops GRANTING Raid to allies. P1 controls Plo Koon (Force enabler), Red Three
#// (SOR_144: printed Raid 1 + "each other friendly Heroism unit gains Raid 1"), and a Heroism ally
#// (SOR_046). Blanking Red Three (power 2 ≤ 4) removes its own Raid and the aura it granted the ally.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_202}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_144:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:1:NOTKEYWORD:Raid

---

# SourceIntact_AllyKeepsGrantedRaid
#// LOF_202 Mind Trick control — blanking an UNRELATED unit (an enemy) leaves Red Three's aura intact, so
#// the Heroism ally still has the granted Raid and Red Three keeps its own. Proves the grant-suppression is
#// scoped to the blanked source, not a blanket removal.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_202}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_144:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid

---

# Offer_PoolAndPerUnitPowerWeights
#// LOF_202 — the offer itself, and the "~BUDGET~<total>~<label>~<mzID>=<weight>…" side channel that
#// carries each unit's POWER to the modal (Core/MZMultiChooseUI.js) so it can grey out what no longer
#// fits after each pick. Every READY unit whose own power is 4 or less is offered — both sides, since
#// "any number of units" names no controller. Both power-6 units are excluded — the AT-ST (SOR_232) and
#// P1's own Plo Koon (LOF_050) — because neither could ever be legal on its own, which is what proves the
#// budget is applied to the pool at all. Power is the CURRENT value, not the printed one, which is why
#// the weights have to be sent rather than looked up from a CardID client-side.
#// The decision is left unanswered so it is still pending to read.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_202}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P1DECISIONTOOLTIP:Exhaust_any_number_of_units_with_4_or_less_combined_power~BUDGET~4~power~theirGroundArena-0=1~theirGroundArena-1=2

---

# OverBudgetPicks_AreDroppedServerSide
#// LOF_202 — the modal's budget is UX, never enforcement: the schema harness hands an answer straight to
#// the handler. Submitting SOR_046 (power 3) AND SOR_063 (power 2) totals 5, over the 4-power budget.
#// The resolver re-measures and re-applies it in submitted order — 3 fits (1 left), 2 does not — so only
#// the first is exhausted and SOR_063 stays ready. Nothing is blanked on the unexhausted unit either:
#// the Force rider applies to the units actually exhausted, not to the units submitted.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_202}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
