# TWI_081 Droid Commando (Unit 4/3, Ground) — "While you control another Separatist unit, this unit
# gains Ambush." Guard: with a friendly Battle Droid (Separatist) alongside, TWI_081 reports HASKEYWORD
# Ambush.

## GIVEN
CommonSetup: gyk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_081:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
