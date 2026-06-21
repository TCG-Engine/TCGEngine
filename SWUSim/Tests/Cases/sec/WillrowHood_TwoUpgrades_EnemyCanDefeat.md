# SEC_061 Willrow Hood — the protection is ONLY while he has EXACTLY 1 friendly upgrade. With 2 friendly
#   upgrades (SOR_120 + SOR_069) the protection is off, so P1's Confiscate defeats the chosen one. Proves
#   the "exactly 1" boundary.

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
