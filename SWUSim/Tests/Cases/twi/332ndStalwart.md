# Coordinate_PlusOneOne
#// TWI_240 332nd Stalwart (Unit 1/2, Ground) — "Coordinate - This unit gets +1/+1." With 3 friendly
#// units (Coordinate active) it is 2/3.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_240:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3
