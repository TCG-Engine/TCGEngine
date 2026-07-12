# TWI_134 Asajj Ventress — attacking alone (no other Separatist attacked this phase), she gets no
# +3/+0 → deals only her base 2 to P2's base.

## GIVEN
CommonSetup: rrk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_134:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
