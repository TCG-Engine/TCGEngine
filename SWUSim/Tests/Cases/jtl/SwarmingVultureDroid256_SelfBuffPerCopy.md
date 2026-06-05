# JTL_256 Swarming Vulture Droid — This unit gets +1/+0 for each OTHER friendly Swarming Vulture Droid.
# With three copies in play, each has two others → +2/+0 → power 4 (HP unchanged at 2).

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_256:1:0
WithP1SpaceArena: JTL_256:1:0
WithP1SpaceArena: JTL_256:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_256
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:2
