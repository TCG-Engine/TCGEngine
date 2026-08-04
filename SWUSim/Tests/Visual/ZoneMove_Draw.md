# VISUAL CHECK — zone slide, deck -> hand (draw)
#
# Visual-only schema. Both players draw at the regroup, so this also shows the OPPONENT's draw
# animating (their cards slide face-down into their hand).
#
# What to look at:
#   • Cards fly from the deck pile into the hand, one per card drawn.
#   • A multi-card draw CASCADES — each card is offset ~60ms from the last, not all at once.
#   • The board does not stall: the whole burst blocks for the LONGEST card, not the sum.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_046 SOR_128 SOR_237 SEC_080 SOR_225]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SOR_237 SEC_080 SOR_225]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
