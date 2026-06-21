# LOF_063 Oggdo Bogdo — "This unit can't attack unless it's damaged." An undamaged Oggdo attacking the
# base is a no-op (the base takes no damage).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_063:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
