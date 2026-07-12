# TWI_193 R2-D2 (Unit 2/4, Ground, cost 2, Cunning/Heroism, Republic/Droid) — "When Played: You may
# discard a card from your hand. If you do, search the top 3 cards of your deck for a card and draw it."
# After playing R2-D2, discarding SOR_095 lets P1 search the top 3 and draw SOR_046.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: [TWI_193 SOR_095]
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DECKCOUNT:3
