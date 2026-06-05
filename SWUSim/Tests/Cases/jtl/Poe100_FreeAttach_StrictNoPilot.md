# JTL_100 Poe Dameron — free-attach strict "0 pilots" rule.
# A Vehicle with a Pilot already on it is NOT offered as a free-attach target.
# Even though the Piloting capacity rule would allow attaching (JTL_249 MF capacity
# raise), the free-attach clause strictly requires "without a Pilot on it."
#
# Setup: SOR_237 (Alliance X-Wing, Space) + JTL_249 (Millennium Falcon, Space, capacity 2).
# Step 1 — Play JTL_100 (idx 0) AS A PILOT onto SOR_237 (Piloting path, cost 2).
#   1 vehicle in play → auto-attaches; SOR_237 gets JTL_100 (IsPilot=true), count=1.
#   Resources: 6 - 2 = 4 remaining.
#
# Step 2 — Play second JTL_100 (now idx 0) AS A UNIT (cost 4):
#   canUnit = true (4 >= 4); canPilot: SOR_237 count=1 < capacity=1 (no R2-D2 etc.) → false.
#   JTL_249 has count=0 < capacity=2 → canPilot = true for JTL_249 target.
#   BOTH canUnit and canPilot are true → Unit/Pilot prompt.
#   Player picks "Unit" → JTL_100 enters ground arena.
#   WhenPlayed fires:
#     1. X-Wing token (JTL_T02) created → space idx 2.
#     2. Free-attach target collection (strict "freeattach" context):
#        - SOR_237: SWUVehiclePilotCount=1, NOT === 0 → excluded.
#        - JTL_249: SWUVehiclePilotCount=0, === 0 → eligible.
#        - JTL_T02 (Vehicle,Fighter): SWUVehiclePilotCount=0, === 0 → eligible.
#        Targets = [JTL_249, JTL_T02].
#     Player declines: AnswerDecision:-
#
# This proves the occupied SOR_237 is excluded from free-attach targets while
# JTL_249 (unoccupied) and JTL_T02 (token, unoccupied) ARE offered.
#
# Final state:
#   Ground: JTL_100 (unit) at idx 0.
#   Space: SOR_237 (idx 0, upgradeCount=1 — JTL_100 pilot), JTL_249 (idx 1, no pilot),
#          JTL_T02 (idx 2, no pilot).
#   SOR_237 still has exactly 1 upgrade (the first JTL_100 pilot, unchanged).
#   JTL_249 still has 0 upgrades (free-attach declined).

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_009/SOR_024
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6
WithP1Hand: JTL_100
WithP1Hand: JTL_100
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: JTL_249:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:Unit
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_100
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_100
P1SPACEARENAUNIT:1:CARDID:JTL_249
P1SPACEARENAUNIT:1:UPGRADECOUNT:0
P1SPACEARENAUNIT:2:CARDID:JTL_T02
P1HANDCOUNT:0
P1RESAVAILABLE:0
