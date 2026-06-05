# JTL_082 Kijimi Patrollers — When Played: Create a TIE Fighter token.

## GIVEN
P1LeaderBase: JTL_005/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_082
WithP1Resources: 2

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:JTL_T01
