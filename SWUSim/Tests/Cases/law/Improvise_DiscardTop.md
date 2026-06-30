# LAW_242 Improvise — if you don't play the top card, you may discard it. Choose Discard -> the top
# card is milled.

## GIVEN
CommonSetup: yyw/bgw/{myResources:1}
WithP1Deck: SOR_237
WithP1Hand: LAW_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Discard

## EXPECT
P1DECKCOUNT:0
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:2
