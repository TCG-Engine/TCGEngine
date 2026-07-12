# TWI_208 Favorable Delegate (Unit 1/5, Ground, cost 2, Cunning, Republic/Official) — "When Played: Draw
# a card. When Defeated: Discard a card from your hand." Playing it draws a card.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:TWI_208}
P1OnlyActions: true
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_208
P1HANDCOUNT:1
P1DECKCOUNT:1
