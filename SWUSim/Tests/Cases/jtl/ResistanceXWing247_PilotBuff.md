# JTL_247 Resistance X-Wing — While this unit has a Pilot on it, it gets +1/+1. With the pilot JTL_034
# attached, its power is 2 (base) + 2 (pilot upgradePower) + 1 (has-pilot) = 5.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_247:1:0
WithP1SpaceArenaUpgrade: 0:JTL_034

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:POWER:5
