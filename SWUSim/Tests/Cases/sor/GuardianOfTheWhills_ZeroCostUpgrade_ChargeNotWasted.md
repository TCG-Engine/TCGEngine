# SOR_061 Guardian of the Whills — attaching a 0-cost upgrade (SHD_068 Public Enemy, cost 0)
# must NOT consume the Guardian's per-round charge (the −1 discount would do nothing on a 0-cost
# card). After the 0-cost upgrade attaches, the charge is still available for the next upgrade.
# SOR_069 Resilient (cost 1) then attaches and gets the −1 → costs 0. Total spent = 0 + 0 = 0.
# 3 ready resources → still 3 left. If the charge were wasted on SHD_068, SOR_069 would cost 1
# → 2 resources left.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1Hand: SHD_068
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1RESAVAILABLE:3
