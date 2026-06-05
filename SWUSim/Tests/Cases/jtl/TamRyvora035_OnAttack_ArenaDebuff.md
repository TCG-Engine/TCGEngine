# JTL_035 Tam Ryvora (pilot) — Attached gains "On Attack: give an enemy unit in this arena -1/-1." The
# host (SOR_237 + pilot) attacks the base; the granted On Attack debuffs SOR_044 to 1/2.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_035
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2SPACEARENAUNIT:0:POWER:1
