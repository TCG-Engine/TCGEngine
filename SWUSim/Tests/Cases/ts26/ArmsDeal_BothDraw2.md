# TS26_068 Arms Deal (Event, cost 2, Aggression) — You and an opponent each draw 2 cards.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:TS26_068}
WithP1Deck: [SEC_080 SOR_095 SOR_046]
WithP2Deck: [SEC_080 SOR_095 SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:2
P2HANDCOUNT:2
P1DECKCOUNT:1
P2DECKCOUNT:1
