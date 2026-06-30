# LAW_242 Improvise (Cunning event, cost 1) — "Look at the top card of your deck. You may play it. It
# costs 1 resource less." Play the top SOR_237 (cost 2 -> 1).

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
WithP1Deck: SOR_237
WithP1Hand: LAW_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1DECKCOUNT:0
P1RESAVAILABLE:0
