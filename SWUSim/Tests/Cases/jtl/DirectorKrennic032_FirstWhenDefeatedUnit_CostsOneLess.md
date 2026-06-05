# JTL_032 Director Krennic — The first unit you play each round that has a When Defeated ability costs 1
# resource less. With Krennic in play, JTL_033 (Onyx Squadron Brute, cost 2, has a When Defeated
# ability) is played for 1, leaving 1 of 2 resources.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_032:1:0
WithP1Hand: JTL_033
WithP1Resources: 2

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_033
P1RESAVAILABLE:1
