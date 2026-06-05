# JTL_257 Flanking Fang Fighter — While you control another Fighter unit, this unit gains Raid 2. With
# SOR_237 (a Fighter) in play, JTL_257 has Raid.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_257:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_257
P1SPACEARENAUNIT:0:HASKEYWORD:Raid
