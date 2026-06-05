# JTL_239 TIE Dagger Vanguard — declining the optional damage leaves the damaged unit untouched.

## GIVEN
P1LeaderBase: JTL_011/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_239
WithP1Resources: 3
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
