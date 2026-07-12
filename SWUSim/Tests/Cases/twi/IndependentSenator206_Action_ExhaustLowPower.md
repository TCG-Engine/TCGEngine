# TWI_206 Independent Senator (Unit 0/4, Ground, cost 1, Separatist/Official) — "Action [2 resources,
# Exhaust]: Exhaust a unit with 4 or less power." Only SOR_095 (power 3) qualifies; TWI_149 (power 6) is
# not offered, so SOR_095 auto-exhausts. The Senator itself exhausts (the cost) and 2 resources are spent.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: TWI_206:1:0
WithP2GroundArena: [SOR_095:1:0 TWI_149:1:0]

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:CARDID:TWI_149
P2GROUNDARENAUNIT:1:READY
P1RESAVAILABLE:0
