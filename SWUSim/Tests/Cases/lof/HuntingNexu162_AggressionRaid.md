# LOF_162 Hunting Nexu (4/4) — "While you control another Aggression unit, this unit gains Raid 2." With
# the Aggression Acolyte controlled, attacking the base deals 4 + 2 (Raid) = 6.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_162:1:0
WithP1GroundArena: LOF_129:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
