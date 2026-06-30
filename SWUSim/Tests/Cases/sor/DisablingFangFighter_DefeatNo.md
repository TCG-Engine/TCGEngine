# SOR_162 Disabling Fang Fighter — DefeatNo
# Player declines — upgrade remains.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SOR_162}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
