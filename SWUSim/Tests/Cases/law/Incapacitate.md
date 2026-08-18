# MinusTwoMinusTwo
#// LAW_131 Incapacitate (Vigilance event, cost 2) — "Give a unit -2/-2 for this phase." Single unit on
#// board (P2's SOR_046, 3/7) -> auto-target -> 1/5.

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_131

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5
P1DISCARDCOUNT:1

---

# MinusTwoMinusTwo_SurvivesTheRequestBoundary
#// LAW_131 Incapacitate — request-boundary guard. Same flow as MinusTwoMinusTwo, but a SECOND enemy unit
#// (SOR_095) is seeded so the "give a unit -2/-2" pick is a real pending choose instead of the
#// single-legal-target auto-resolve, and the game then round-trips through serialization
#// (SimulateRequestBoundary) while that pick is open. In a real game the answer arrives in a fresh
#// process, so the event's deferred -2/-2-for-this-phase payload must be serialized state rather than a
#// transient in-memory continuation. Choosing SOR_046 must still take it 3/7 -> 1/5.

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_131

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5
P1DISCARDCOUNT:1

---

# ShrinkToZeroHP_DefeatsTheUnit
#// LAW_131 Incapacitate — a stat DEBUFF that takes a unit's HP to 0 must defeat it, not leave a 0-HP unit
#// standing. SOR_108 is a 1/2, so -2/-2 puts it at 0 remaining HP: it leaves the arena and goes to its
#// owner's discard the moment the debuff lands, in the same action. Boundary partner of MinusTwoMinusTwo,
#// where the 3/7 host has HP to spare and survives at 1/5.

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_108:1:0
WithP1Hand: LAW_131

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1

---

# OfferPool_AnyUnitEitherSideEitherArena
#// LAW_131 Incapacitate — "Give A UNIT -2/-2" names no controller and no arena, so the pool is every unit
#// in play, FRIENDLY ones included (debuffing your own unit is legal, if rarely wanted). Discriminating
#// board: a friendly ground unit, a friendly space unit, an enemy ground unit and an enemy space unit are
#// all in. The two existing sections only ever offer enemy units, so neither could see a pool wrongly
#// narrowed to the opponent's side.

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_131

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0
