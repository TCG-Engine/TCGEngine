# CombinedPowerExhaust
#// LOF_202 Mind Trick — Exhaust any number of units with a combined power of 4 or less. SOR_059 (power 1)
#// and SOR_063 (power 2) total 3 ≤ 4, so both are exhausted.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_202}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

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
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
