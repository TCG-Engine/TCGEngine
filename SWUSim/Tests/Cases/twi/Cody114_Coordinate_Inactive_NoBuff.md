# TWI_114 Clone Commander Cody — with only 2 friendly units (Coordinate inactive), the Clone token
# stays 2/2 and does NOT gain Overwhelm.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_114:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:2
P1GROUNDARENAUNIT:1:NOTKEYWORD:Overwhelm
