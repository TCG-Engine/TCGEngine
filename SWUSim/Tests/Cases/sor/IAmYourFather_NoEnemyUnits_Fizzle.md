# SOR_233 I Am Your Father — with no enemy unit to target, the event fizzles cleanly (no decision,
# no draw) and goes to the discard.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1NODECISION
