# SOR_043 Superlaser Blast — with no units in play it resolves cleanly (no crash, no decision) and goes
# to the discard.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_043
WithP1Resources: 8

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1NODECISION
