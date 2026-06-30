# LAW_235 Lady Proxima (1/5 ground, Underworld) — "Action [Exhaust]: Create a Credit token." Using the
# action creates 1 Credit token and exhausts her.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_235:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1CREDITCOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
