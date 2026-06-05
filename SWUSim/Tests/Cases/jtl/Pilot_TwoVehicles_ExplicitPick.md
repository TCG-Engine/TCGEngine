# JTL_035 Tam Ryvora: two empty Vehicles in play — MZCHOOSE picker is shown.
# With 2 resources (pilot cost 2, not enough for unit cost 3) and TWO empty Vehicles,
# SWUQueuePilotVehiclePick must show the MZCHOOSE picker (not auto-attach).
# Player answers with mySpaceArena-1 (the second vehicle); the pilot attaches there.
# The first vehicle (mySpaceArena-0) remains unmodified (no upgrade).
# SOR_225 TIE/ln Fighter: 2/1. JTL_035 upgradePower=+2, upgradeHp=+2. Chosen host → 4/3.

## GIVEN
P1LeaderBase: SOR_001/SOR_019
P2LeaderBase: SOR_001/SOR_019
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1Hand: JTL_035
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:SOR_225
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_035
P1SPACEARENAUNIT:1:POWER:4
P1SPACEARENAUNIT:1:HP:3
P1HANDCOUNT:0
P1RESAVAILABLE:0
