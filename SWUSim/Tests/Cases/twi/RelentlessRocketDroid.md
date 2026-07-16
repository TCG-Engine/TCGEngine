# PlusPowerWhileTrooper
#// TWI_163 Relentless Rocket Droid (Unit 3/5, Ground) — "While you control another Trooper unit, this
#// unit gets +2/+0." With a friendly Battle Droid (Trooper) alongside → 5 power.

## GIVEN
CommonSetup: rrk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_163:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
