# SOR_081 Seasoned Shoretrooper (2/3) — "While you control 6 or more resources,
# this unit gets +2/+0." With 6 resources it reads 4/3.

## GIVEN
CommonSetup: grk/grk/{myResources:6}
WithP1GroundArena: SOR_081:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
