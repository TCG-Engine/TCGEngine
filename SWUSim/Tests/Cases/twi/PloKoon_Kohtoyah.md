# Coordinate_Raid3
#// TWI_196 Plo Koon (Unit 3/6, Ground) — "Ambush. Coordinate - Raid 3." With 3 friendly units
#// (Coordinate active), Plo attacking gets +3/+0 → deals 3+3 = 6 to P2's base.

## GIVEN
CommonSetup: yyw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_196:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
