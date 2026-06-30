# LOF_169 Invasion Control Ship (5/9) — "Friendly Droid units gain Raid 2." The friendly Droid (SEC_080,
# 3 power) attacking the base deals 3 + 2 (Raid) = 5.

## GIVEN
CommonSetup: ggk/rrw
P1OnlyActions: true
WithP1GroundArena: LOF_169:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:5
