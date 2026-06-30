# Restore: unit heals player's base on attack
# Sundari Peacekeeper (SHD_098, Restore 2, 3 power) attacks P2 base.
# P1 base starts at 3 damage; after attack it heals 2 → 1 damage.
# P2 base takes 3 damage (Peacekeeper's power).

## GIVEN
CommonSetup: ggw/grw/{myBaseDamage:3}
WithP1GroundArena: SOR_243:1:0   # Regional Sympathizers

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:1
