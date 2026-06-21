# LOF_025 Temple of Destruction — "When a friendly unit deals 3 or more combat damage to an enemy
# base: The Force is with you." A 3-power unit attacks P2's base, dealing exactly 3 combat damage → P1
# gains the Force.

## GIVEN
P1LeaderBase: SOR_002/LOF_025
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASFORCE
P2BASEDMG:3
