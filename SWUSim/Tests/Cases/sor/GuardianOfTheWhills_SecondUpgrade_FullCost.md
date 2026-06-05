# SOR_061 Guardian of the Whills — only the FIRST upgrade each round is discounted. Two SOR_069
# (cost 1) on the same Guardian: the first costs 0 (charge spent), the second costs the full 1.
# 3 ready resources → 0 + 1 = 2 left. (If the charge weren't consumed, both would be free → 3 left.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1Hand: SOR_069
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1RESAVAILABLE:2
