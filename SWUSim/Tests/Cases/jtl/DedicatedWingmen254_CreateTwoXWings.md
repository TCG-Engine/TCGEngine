# JTL_254 Dedicated Wingmen (event) — Create 2 X-Wing tokens.

## GIVEN
P1LeaderBase: JTL_004/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_254
WithP1Resources: 4

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_T02
P1SPACEARENAUNIT:1:CARDID:JTL_T02
