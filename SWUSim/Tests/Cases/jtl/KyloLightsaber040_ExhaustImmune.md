# LOF_040 Kylo Ren's Lightsaber — "If attached unit is a Force unit, it gains: 'This unit can't be
# exhausted by enemy card abilities.'" Rey (a Force unit) carries the Lightsaber; P1 plays Evasive
# Maneuver (JTL_262: exhaust a unit) at her, but she stays ready.

## GIVEN
CommonSetup: yyk/rrk/{myResources:8;handCardIds:JTL_262}
P1OnlyActions: true
WithP2GroundArena: LAW_149:1:0
WithP2GroundArenaUpgrade: 0:LOF_040

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:LAW_149
P2GROUNDARENAUNIT:0:READY
