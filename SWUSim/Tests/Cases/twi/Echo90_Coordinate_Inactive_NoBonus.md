# TWI_090 Echo — with only 2 friendly units (Coordinate inactive), no +2/+2: stays 2/2.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_090:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2
