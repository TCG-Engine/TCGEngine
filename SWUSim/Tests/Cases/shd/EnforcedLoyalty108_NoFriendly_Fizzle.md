# SHD_108 Enforced Loyalty — with no friendly unit to defeat, the effect fizzles: no defeat, no draw.
# The event still lands in the discard.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_108
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1NODECISION
