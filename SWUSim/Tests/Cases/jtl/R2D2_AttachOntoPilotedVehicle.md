# R2-D2 (JTL_245) attaches onto a Vehicle that already has a Pilot on it.
# CardPilotIgnoresOccupied(JTL_245)=true lets R2-D2 bypass the count<capacity gate.
#
# Setup: SOR_237 (Alliance X-Wing 2/3) in Space Arena.
# Hand: JTL_100 (Poe Dameron, cost 4, piloting 2, Command+Heroism) at index 0,
#        JTL_245 (R2-D2, cost 1, piloting 0, Heroism) at index 1.
# Resources: 3. Leader SOR_005 (Vigilance+Heroism) + Base SOR_022 (Command) covers all aspects.
#
# Step 1 — Play JTL_100 (index 0) as Pilot onto X-Wing:
#   canUnit = false (3 < 4), canPilot = true (3 >= 2, X-Wing present, capacity 1 count 0)
#   → Pilot-only short-circuit; 1 vehicle → PASSPARAMETER auto-resolve (no vehicle pick prompt).
#   Resources consumed: 2; remaining: 1.
#
# Step 2 — Play JTL_245 R2-D2 (now index 0) as Pilot onto now-occupied X-Wing:
#   canUnit = true (1 >= 1), canPilot = true (1 >= 0, X-Wing valid via ignoresOccupied)
#   → Both options available; Unit/Pilot prompt raised.
#   Resources consumed: 0; remaining: 1.
#
# Final: SOR_237 hosts JTL_100 (subcard 0) + JTL_245 (subcard 1) → UPGRADECOUNT:2
# Stats: base 2/3 + JTL_100 (+2/+3) + JTL_245 (+1/+1) = 5/7

## GIVEN
P1LeaderBase: SOR_005/SOR_022
P2LeaderBase: SOR_005/SOR_022
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 3
WithP1Hand: JTL_100
WithP1Hand: JTL_245
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_100
P1SPACEARENAUNIT:0:UPGRADE:1:CARDID:JTL_245
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:7
P1HANDCOUNT:0
P1RESAVAILABLE:1
