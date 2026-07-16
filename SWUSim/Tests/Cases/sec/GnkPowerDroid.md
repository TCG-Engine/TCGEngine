# OnAttack_NextUnitCostsLess
#// SEC_110 GNK Power Droid (Ground, 1/3) — On Attack: the next unit you play this phase costs 1 resource
#//   less. SEC_110 attacks P2's base (arming the discount); P1 then plays SOR_046 (cost 4 → 3), leaving 1
#//   of 4 resources.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_110:1:0
WithP1Hand: SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:1
