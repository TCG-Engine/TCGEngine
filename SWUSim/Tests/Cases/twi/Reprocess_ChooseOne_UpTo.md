# TWI_088 Reprocess — "up to 4": choose only ONE of the two discard units. That one goes to the deck
# bottom (deck 1 → 2) and exactly 1 Battle Droid is created ("that many"). The unchosen unit stays in
# discard alongside the event (discard count 2).

## GIVEN
CommonSetup: gyk/grw/{myResources:3;handCardIds:TWI_088;discardCardIds:SEC_080}
P1OnlyActions: true
WithP1Discard: JTL_069
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1DISCARDCOUNT:2
P1DECKCOUNT:2
