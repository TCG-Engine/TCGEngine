# TWI_094 Shaak Ti (Unit 3/4, Ground) — "Each friendly token unit gets +1/+0." A friendly Battle Droid
# token (1/1) becomes 2/1 while Shaak Ti is in play.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_094:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:1
