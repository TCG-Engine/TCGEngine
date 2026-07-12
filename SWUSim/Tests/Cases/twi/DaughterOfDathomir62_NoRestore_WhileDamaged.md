# TWI_062 Daughter of Dathomir — while DAMAGED (1 damage on her) she does NOT have Restore 2, so
# attacking heals nothing from P1's base (stays at 5).

## GIVEN
CommonSetup: bbk/grw/{myResources:0;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: TWI_062:1:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1BASEDMG:5
