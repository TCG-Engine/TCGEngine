# Millennium Falcon (JTL_249) base power before pilots — proves +1/+0 per Pilot delta.
#
# MF has power=3 printed. With 0 pilots attached, SWUVehiclePilotCount=0,
# so ObjectCurrentPower should return 3 (no per-pilot bonus).
#
# This test establishes the hull-only baseline; MillenniumFalcon_PowerPerPilot.md
# then proves the delta (+2 for 2 pilots is the difference between 3 and 8).
#
# No hand cards — MF is already in space arena with no upgrades/pilots.
# Resources = 0 (no plays needed).

## GIVEN
P1LeaderBase: SOR_005/SOR_022
P2LeaderBase: SOR_005/SOR_022
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 0
WithP1SpaceArena: JTL_249:1:0

## WHEN

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_249
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:4
