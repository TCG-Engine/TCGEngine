# JTL_035 Tam Ryvora: both Unit and Pilot affordable → OPTIONCHOOSE Unit/Pilot prompt.
# With 3 resources (covers both unit cost 3 and pilot cost 2) and an empty Vehicle,
# PlayHand raises the Unit/Pilot OPTIONCHOOSE. Answering Pilot then picks the vehicle.
# Leader SOR_001 (Vigilance+Villainy) + Base SOR_019 (Vigilance) = no aspect penalty.
# SOR_225 TIE/ln Fighter: 2/1. JTL_035 upgradePower=+2, upgradeHp=+2. Host becomes 4/3.

## GIVEN
P1LeaderBase: SOR_001/SOR_019
P2LeaderBase: SOR_001/SOR_019
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 3
WithP1Hand: JTL_035
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_035
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:3
P1HANDCOUNT:0
P1RESAVAILABLE:1
