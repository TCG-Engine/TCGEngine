# MillReturnAggression
#// LAW_203 Daring Delve (Aggression event, cost 1) — "Discard 2 cards from your deck. You may return an
#// Aggression card discarded this way to your hand." Mill SOR_128 (Aggression) + SOR_237 (Heroism);
#// return SOR_128 to hand.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithP1Deck: SOR_128
WithP1Deck: SOR_237
WithP1Hand: LAW_203

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:2
