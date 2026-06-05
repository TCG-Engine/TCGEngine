# SOR_251 Confiscate — single unit with two upgrades, player picks which to defeat
# P2 unit has LOF_215 (index 0) and SOR_215 (index 1). Player defeats index 1
# via the staged TempZone pick (myTempZone-1 → matchIdx[1]). One upgrade remains.

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215
WithP2GroundArenaUpgrade: 0:SOR_215

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
