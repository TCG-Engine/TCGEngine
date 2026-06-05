# JTL_034 Interceptor Ace (pilot) — Attached unit gains Grit. SOR_237 with the pilot attached and 2
# damage gets +2/+0 from the granted Grit.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:2
WithP1SpaceArenaUpgrade: 0:JTL_034

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:POWER:6
