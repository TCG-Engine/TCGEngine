# SOR_170 Power Failure — token upgrades are set aside, not discarded
# P2 unit has a Shield token (SOR_T02). Token is set aside: shield gone, no discard entry.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_170}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade:0:SOR_T02
WithP2GroundArenaUpgrade:0:SOR_T02
WithP2GroundArenaUpgrade:0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0&myTempZone-1&myTempZone-2

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1
