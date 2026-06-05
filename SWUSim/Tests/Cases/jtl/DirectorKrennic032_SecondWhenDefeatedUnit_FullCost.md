# JTL_032 Director Krennic — only the FIRST such unit each round gets the discount. Playing two When
# Defeated units (each cost 2): the first costs 1, the second costs the full 2, so 3 resources are
# exactly consumed.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_032:1:0
WithP1Hand: JTL_033
WithP1Hand: JTL_033
WithP1Resources: 3

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1RESAVAILABLE:0
