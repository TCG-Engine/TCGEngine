# LOF_025 Temple of Destruction — negative/boundary: a 2-power unit attacks P2's base, dealing only 2
# combat damage (< 3), so no Force token is created.

## GIVEN
P1LeaderBase: SOR_002/LOF_025
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NOFORCE
P2BASEDMG:2
