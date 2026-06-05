# JTL_045 Hera Syndulla (pilot) — Attached unit gains Restore 1. The host (SOR_237 + pilot, power 4)
# attacks the base for 4 and Restore 1 heals P1's base from 3 to 2.

## GIVEN
P1LeaderBase: JTL_001/SOR_020:3
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_045

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:4
P1BASEDMG:2
