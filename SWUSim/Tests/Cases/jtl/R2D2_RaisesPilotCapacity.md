# R2-D2 (JTL_245) raises Vehicle pilot capacity from 1 to 2, allowing a second Pilot.
# CardPilotAddsCapacity(JTL_245)=true adds +1 to SWUVehiclePilotCapacity once attached.
# Also proves the JTL_100 context split: in 'piloting' context JTL_100 uses the generic
# count<capacity rule (not the strict "0 pilots" free-attach clause), so it can attach
# onto an R2-occupied X-Wing as pilot #2 when capacity permits.
#
# Setup: SOR_237 (Alliance X-Wing 2/3) in Space Arena.
# Hand: JTL_245 (R2-D2, cost 1, piloting 0, Heroism) at index 0,
#        JTL_100 (Poe Dameron, cost 4, piloting 2, Command+Heroism) at index 1.
# Resources: 5. Leader SOR_005 (Vigilance+Heroism) + Base SOR_022 (Command) covers all aspects.
#
# Step 1 — Play JTL_245 R2-D2 (index 0) as Pilot onto X-Wing:
#   canUnit = true (5 >= 1), canPilot = true (5 >= 0, X-Wing present, capacity 1 count 0)
#   → Both options; Unit/Pilot prompt. Choose Pilot. 1 vehicle → PASSPARAMETER auto-resolve.
#   Resources consumed: 0; remaining: 5.
#   After attach: SWUVehiclePilotCapacity = 1(base) + 1(R2 grant) = 2; count = 1.
#
# Step 2 — Play JTL_100 Poe (now index 0) as Pilot onto R2-occupied X-Wing:
#   canUnit = true (5 >= 4), canPilot = true (5 >= 2, capacity 2 > count 1, 'piloting' context)
#   → Both options; Unit/Pilot prompt. Choose Pilot. 1 vehicle → PASSPARAMETER auto-resolve.
#   Resources consumed: 2; remaining: 3.
#
# Final: SOR_237 hosts JTL_245 (subcard 0) + JTL_100 (subcard 1) → UPGRADECOUNT:2
# Stats: base 2/3 + JTL_245 (+1/+1) + JTL_100 (+2/+3) = 5/7

## GIVEN
P1LeaderBase: SOR_005/SOR_022
P2LeaderBase: SOR_005/SOR_022
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 5
WithP1Hand: JTL_245
WithP1Hand: JTL_100
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_245
P1SPACEARENAUNIT:0:UPGRADE:1:CARDID:JTL_100
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:7
P1HANDCOUNT:0
P1RESAVAILABLE:3
