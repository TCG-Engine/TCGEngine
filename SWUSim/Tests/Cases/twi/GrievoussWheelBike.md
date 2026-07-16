# CostsLessOnGrievous_GrantsOverwhelm
#// TWI_236 Grievous's Wheel Bike (Upgrade +3/+3, Villainy, cost 4) — "While playing this upgrade on
#// General Grievous, it costs 2 resources less. Attach to a non-Vehicle unit. Attached unit gains
#// Overwhelm." Played onto TWI_034 General Grievous (non-Vehicle): costs 4-2 = 2 (played with exactly 2
#// resources), and TWI_034 gains Overwhelm.

## GIVEN
CommonSetup: rrk/grw/{myResources:2;handCardIds:TWI_236}
P1OnlyActions: true
WithP1GroundArena: TWI_034:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_034
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1RESAVAILABLE:0
