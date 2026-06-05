# JTL_102 Resistance Blue Squadron — declining the optional damage leaves the enemy untouched.

## GIVEN
P1LeaderBase: JTL_007/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1Hand: JTL_102
WithP1Resources: 4
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
