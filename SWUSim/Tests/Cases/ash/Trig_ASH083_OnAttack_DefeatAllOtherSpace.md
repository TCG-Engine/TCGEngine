# ASH_083 Summa-verminoth (Space, 15/15, Sentinel, cost 12) — On Attack: defeat all OTHER space units.
# Summa attacks P2's base; its On Attack defeats the friendly SOR_237 and the enemy SOR_225, leaving only
# Summa itself in the space arenas.
## GIVEN
CommonSetup: bbw/bbk
WithP1SpaceArena: ASH_083:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:0
