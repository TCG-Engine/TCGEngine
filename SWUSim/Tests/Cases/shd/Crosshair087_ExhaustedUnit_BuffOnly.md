# SHD_087 Crosshair — when the unit is exhausted, the [Exhaust] deal-power action is unavailable, so only
# the [2 resources] buff remains: it resolves directly (no menu), giving +1/+0.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SHD_087:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1RESAVAILABLE:0
