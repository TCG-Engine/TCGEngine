# SHD_156 — the discard is conditional on the opponent controlling MORE resources than you. Here P1
# has 5 resources and P2 only 3 → P1 still draws, but P2 keeps its hand (no discard).

## GIVEN
CommonSetup: rrw/rrw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_156
WithP1Deck: SOR_095
WithP2Resources: 3
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:1
P2DISCARDCOUNT:0
