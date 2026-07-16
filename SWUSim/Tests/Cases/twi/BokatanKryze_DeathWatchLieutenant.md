# PlusPowerWhileTrooper
#// TWI_130 Bo-Katan Kryze (Unit 2/3, Ground) — "While you control another Trooper unit, this unit gets
#// +1/+0." A friendly Battle Droid token (Trooper) alongside → Bo-Katan is 3/3.

## GIVEN
CommonSetup: rrk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_130:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
