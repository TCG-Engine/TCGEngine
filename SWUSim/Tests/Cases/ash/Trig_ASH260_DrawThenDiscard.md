# ASH_260 Mos Espa Watermonger (Ground, 1/3, cost 2) — When Played: you may draw a card; if you do,
# discard a card. P1 accepts: draws SOR_095 (the only deck card) and then discards it (the only hand card,
# auto-resolved), netting no hand change and one card in the discard.
## GIVEN
CommonSetup: bbw/bbk/{myResources:2;handCardIds:ASH_260}
WithP1Deck: [SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
