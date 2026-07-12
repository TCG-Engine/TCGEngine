# TWI_158 Clone Heavy Gunner (Unit 1/3, Ground) — "Coordinate - This unit gets +2/+0." With 3 friendly
# units (Coordinate active) power is 1+2 = 3, HP stays 3; with only 2 (inactive) power stays 1.

## GIVEN
CommonSetup: rrw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_158:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
