# JTL_113 Homestead Militia — While you control 6 or more resources, this unit gains Sentinel.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_113:1:0
WithP1Resources: 6

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_113
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
