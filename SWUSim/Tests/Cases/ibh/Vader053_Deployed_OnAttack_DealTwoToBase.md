# IBH_053 Darth Vader (deployed) — On Attack: deal 2 damage to a base. Vader deploys (6 resources),
#   attacks the enemy base: combat 3 + On Attack 2 = 5.

## GIVEN
P1LeaderBase: IBH_053:1:0:0/SOR_026
P2LeaderBase: SOR_005/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
