# Coordinate_GrantsGrit
#// TWI_050 Luminara Unduli (Unit 4/9, Ground) — "Coordinate - Grit." Guard for the already-wired
#// HasConditionalKeyword_Grit case. With 3 friendly units (Coordinate active) and 2 damage on her, Grit
#// gives +1/+0 per damage → power 4+2 = 6, and she reports HASKEYWORD Grit.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_050:1:2
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:6
