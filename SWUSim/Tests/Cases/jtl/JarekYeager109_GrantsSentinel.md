# JTL_109 Jarek Yeager (pilot) — While you control a ground unit and a space unit, attached unit gains
# Sentinel. The host SOR_237 (space) with a friendly ground unit (SOR_046) in play gains Sentinel.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_109
WithP1GroundArena: SOR_046:1:0

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
