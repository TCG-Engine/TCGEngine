# CantAttack
#// LOF_044 Loth-Wolf — Sentinel + "This unit can't attack." Attacking the base is a no-op (no damage).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_044:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
