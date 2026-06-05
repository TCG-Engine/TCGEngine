# JTL_115 Clone Combat Squadron — This unit gets +1/+1 for each OTHER friendly space unit. With two
# other friendly space units, JTL_115 (3/3) is 5/5.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_115:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_115
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:5
