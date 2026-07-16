# Coordinate_GrantsSaboteur
#// TWI_243 Republic Commando (Unit 2/5, Ground) — "Coordinate - Saboteur." Guard for the already-wired
#// HasConditionalKeyword_Saboteur case: with 3 friendly units (Coordinate active) it reports HASKEYWORD
#// Saboteur; with only 2 it does not.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_243:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
