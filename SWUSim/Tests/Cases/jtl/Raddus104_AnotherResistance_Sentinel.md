# JTL_104 Raddus — While you control another Resistance card, this unit gains Sentinel. With another
# Resistance unit (JTL_099) in play, Raddus has Sentinel.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:0
WithP1GroundArena: JTL_099:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_104
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
