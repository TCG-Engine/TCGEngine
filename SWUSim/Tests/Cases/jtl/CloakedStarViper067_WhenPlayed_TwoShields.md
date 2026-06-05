# JTL_067 Cloaked StarViper — When Played: Give 2 Shield tokens to this unit.

## GIVEN
P1LeaderBase: JTL_004/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_067
WithP1Resources: 4

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_067
P1SPACEARENAUNIT:0:SHIELDCOUNT:2
