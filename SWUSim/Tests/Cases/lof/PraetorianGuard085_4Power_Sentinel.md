# LOF_085 Praetorian Guard (2/5) — "While you control a unit with 4 or more power, this unit gains
# Sentinel." With the 4-power LAW_124 controlled, it has Sentinel.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_085:1:0
WithP1GroundArena: LAW_124:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
