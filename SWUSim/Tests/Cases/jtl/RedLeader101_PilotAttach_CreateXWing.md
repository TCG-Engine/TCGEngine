# JTL_101 Red Leader — When a Pilot upgrade attaches to this unit: Create an X-Wing token. Playing the
# pilot JTL_034 onto Red Leader creates an X-Wing (2 space units).

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 9
WithP1Hand: JTL_034
WithP1SpaceArena: JTL_101:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENACOUNT:2
