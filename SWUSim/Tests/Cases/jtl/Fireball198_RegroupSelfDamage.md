# JTL_198 Fireball — When the regroup phase starts: Deal 1 damage to this unit. P1 passes to end the
# action phase; at regroup start Fireball takes 1 damage.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_198:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
