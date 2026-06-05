# SOR_162 Disabling Fang Fighter — OwnUpgrade
# "An upgrade" has no enemy restriction. P1's own unit has the only upgrade —
# auto-resolved after YES.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SOR_162}
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
