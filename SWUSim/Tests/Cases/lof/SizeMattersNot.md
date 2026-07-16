# StatOverride
#// LOF_056 Size Matters Not — "Attached unit's printed power is considered to be 5 and its printed HP is
#// considered to be 5." On Plo Koon (6/8) the override makes him exactly 5/5.

## GIVEN
CommonSetup: bbw/rrk
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_056

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
