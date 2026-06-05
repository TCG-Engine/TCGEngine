# JTL_035 Tam Ryvora played as a Pilot onto a friendly Vehicle.
# With exactly the piloting cost (2) in resources but not enough for unit cost (3),
# and one empty Vehicle, PlayHand short-circuits straight to Vehicle pick (no Unit option).
# JTL_035: unit cost 3, piloting cost 2, aspects Vigilance+Villainy.
# Leader SOR_001 (Vigilance+Villainy) + Base SOR_019 (Vigilance) = no aspect penalty.
# Pilot cost = 2. canUnit = false (2 < 3). canPilot = true (2 >= 2, vehicle present).
# → Pilot-only short-circuit: MZCHOOSE vehicle directly.
# SOR_225 TIE/ln Fighter: 2/1. JTL_035 upgradePower=+2, upgradeHp=+2. Host becomes 4/3.

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

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_035
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:3
P1HANDCOUNT:0
P1RESAVAILABLE:0
