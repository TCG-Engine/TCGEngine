# SOR_187 I Had No Choice — with no non-leader units in play the event fizzles cleanly (no decision)
# and goes to the discard.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_187
WithP1Resources: 9

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1NODECISION
