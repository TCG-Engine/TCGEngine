# Sentinel_WhileOpponentThreeUnits
#// TWI_054 Duchess's Champion (Unit 1/8, Ground) — "While an opponent controls 3 or more units, this
#// unit gains Sentinel." Guard: with 3 enemy units in play, TWI_054 reports HASKEYWORD Sentinel.

## GIVEN
CommonSetup: bbk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_054:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
