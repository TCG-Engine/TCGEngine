# TWI_175 Strategic Analysis (Event, cost 5, Aggression, Plan) — "Draw 3 cards."

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;handCardIds:TWI_175}
P1OnlyActions: true
WithP1Deck: [SOR_046 SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:3
P1DECKCOUNT:1
