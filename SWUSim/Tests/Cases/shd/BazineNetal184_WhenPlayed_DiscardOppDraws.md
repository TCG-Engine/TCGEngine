# SHD_184 Bazine Netal (2-cost 1/3) — "When Played: Look at an opponent's hand. You may discard 1
# of those cards. If you do, that player draws a card." P1 plays her, discards one of P2's two hand
# cards → P2 draws: hand back to 2, discard 1, deck empty.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;theirhandCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP1Hand: SHD_184
WithP2Deck: SOR_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:1
P2DECKCOUNT:0
