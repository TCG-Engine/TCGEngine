# Coordinate_GrantsSentinel
#// TWI_061 Infantry of the 212th (Unit 2/4, Ground) — "Coordinate - Sentinel." Guard for the
#// already-wired HasConditionalKeyword_Sentinel case. With 3 friendly units (Coordinate active) she
#// reports HASKEYWORD Sentinel; with only herself + 1 (inactive), she does not.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_061:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
