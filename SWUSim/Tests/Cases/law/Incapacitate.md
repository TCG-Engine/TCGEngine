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
