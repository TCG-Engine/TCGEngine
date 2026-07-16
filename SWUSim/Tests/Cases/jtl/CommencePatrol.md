# DiscardToDeckBottom_XWing
#// JTL_205 — Put another card in a discard pile on the bottom of its owner's deck. If you do, create an
#// X-Wing token. P1 picks SOR_095 from P2's discard → it goes to the bottom of P2's deck → P1 gets an
#// X-Wing token in the space arena.

## GIVEN
CommonSetup: byw/byk/{myResources:5;handCardIds:JTL_205;theirDiscardCardIds:SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirDiscard-0

## EXPECT
P2DISCARDCOUNT:0
P2DECKCOUNT:1
P1SPACEARENACOUNT:1
