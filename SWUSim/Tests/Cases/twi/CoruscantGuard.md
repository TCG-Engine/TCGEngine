# Coordinate_GrantsAmbush
#// TWI_106 Coruscant Guard (Unit 3/2, Ground) — "Coordinate - Ambush." Guard for the already-wired
#// HasConditionalKeyword_Ambush case: with 3 friendly units (Coordinate active) she reports HASKEYWORD
#// Ambush.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_106:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
