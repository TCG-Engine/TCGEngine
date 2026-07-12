# TWI_107 Patrolling V-Wing (Unit 1/1, Space, cost 2, Command) — "When Played: Draw a card."

## GIVEN
CommonSetup: ggw/rrk/{myResources:2;handCardIds:TWI_107}
P1OnlyActions: true
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_107
P1HANDCOUNT:1
P1DECKCOUNT:1
