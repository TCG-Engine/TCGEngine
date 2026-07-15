# TS26_018 Jendirian Valley (Unit 1/5 space, cost 4) — Restore 1 + When Played: search the top 8 cards of
# your deck for a card and resource it. SEC_080 is chosen from the deck and put into play as a resource
# (resource count 4 → 5, deck 3 → 2).
## GIVEN
CommonSetup: bgk/rrk/{myResources:4;handCardIds:TS26_018}
WithP1Deck: [SEC_080 SOR_095 SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080
## EXPECT
P1RESCOUNT:5
P1DECKCOUNT:2
