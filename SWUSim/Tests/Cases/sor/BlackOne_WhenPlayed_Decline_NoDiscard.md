# SOR_147 Black One — the discard/draw is optional ("You may"). Declining leaves the hand
# intact (the 2 non-Black-One cards remain), nothing is discarded, and no card is drawn.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_147
WithP1Hand: SOR_128
WithP1Hand: SOR_128
WithP1Deck: SOR_128
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:0
P1DECKCOUNT:3
