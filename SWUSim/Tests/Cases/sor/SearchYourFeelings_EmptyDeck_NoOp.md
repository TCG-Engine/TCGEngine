# SOR_042 Search Your Feelings — with an empty deck there is nothing to search: no decision, the event
# just resolves to the discard.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_042
WithP1Resources: 4

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
