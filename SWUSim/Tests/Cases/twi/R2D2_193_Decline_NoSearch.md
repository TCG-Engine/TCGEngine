# TWI_193 R2-D2 — declining the optional discard (AnswerDecision:-) runs no search; the hand keeps SOR_095
# and the deck is untouched.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: [TWI_193 SOR_095]
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1DECKCOUNT:4
