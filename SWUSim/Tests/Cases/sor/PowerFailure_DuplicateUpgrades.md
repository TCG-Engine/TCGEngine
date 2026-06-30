# SOR_170 Power Failure — host with two IDENTICAL upgrades, defeat both
# Both copies of SOR_120 are staged as distinct TempZone entries (myTempZone-0/-1 →
# matchIdx[0]/[1]). Selecting both defeats both copies; descending-defeat is index-shift
# safe even though the CardIDs are identical (positional map, no CardID re-matching).

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_170}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:2
P1DISCARDCOUNT:1
