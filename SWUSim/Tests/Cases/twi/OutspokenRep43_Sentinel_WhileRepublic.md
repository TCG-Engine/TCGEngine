# TWI_043 Outspoken Representative — "While you control another Republic unit, this unit gains
# Sentinel." Guard: with a friendly Clone Trooper (Republic) alongside, TWI_043 reports HASKEYWORD
# Sentinel.

## GIVEN
CommonSetup: rrk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_043:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
