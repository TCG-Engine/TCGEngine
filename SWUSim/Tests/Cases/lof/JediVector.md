# JediAndLightsaber
#// LOF_244 Jedi Vector (1/3) — "+1/+0 if you control another Jedi unit and +1/+0 if you control a
#// Lightsaber upgrade." With the Jedi Plo Koon (carrying a Lightsaber), it is 1 + 1 + 1 = 3 power.

## GIVEN
CommonSetup: bbw/rrk
WithP1SpaceArena: LOF_244:1:0
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:SOR_053

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
