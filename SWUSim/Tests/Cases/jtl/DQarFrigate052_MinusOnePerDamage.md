# JTL_052 D'Qar Cargo Frigate — This unit gets -1/-0 for each damage on it. With 3 damage, the 6/7
# frigate is at power 3.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_052:1:3

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_052
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:7
