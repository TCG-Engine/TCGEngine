# TWI_164 Hevy (Unit 4/4, Ground) — "Coordinate - Raid 2." With 3 friendly units (Coordinate active),
# Hevy attacking gets +2/+0 → deals 4+2 = 6 to P2's base.

## GIVEN
CommonSetup: rrk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_164:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
