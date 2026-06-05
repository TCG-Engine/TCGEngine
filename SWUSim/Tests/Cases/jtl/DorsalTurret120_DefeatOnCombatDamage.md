# JTL_120 Dorsal Turret — Attached Vehicle gains "When this unit deals combat damage to a unit while
# attacking: defeat that unit." SOR_237 (with the turret) hits SOR_044 in combat; SOR_044 survives the
# damage but is then defeated by the turret.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_120
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:1
