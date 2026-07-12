# TWI_058 Padawan Starfighter (Unit 1/3, Space) — "While you control a Force unit or a Force upgrade,
# this unit gets +1/+1." With a friendly Force unit (SOR_049) in play, the Starfighter is 2/4.

## GIVEN
CommonSetup: bbw/grw/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: TWI_058:1:0
WithP1GroundArena: SOR_049:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:4
