# TWI_201 Aid from the Innocent (Event, cost 5, Cunning/Heroism) — "Search the top 10 cards of your deck
# for 2 Heroism non-unit cards and discard them. (Put the other cards on the bottom of your deck in a
# random order.)" Top 10 hold two Heroism events (SOR_246, SOR_200) + 8 fillers; both are discarded and
# the 8 fillers go to the bottom.
## GIVEN
CommonSetup: yyw/rrk/{myResources:5;handCardIds:TWI_201}
P1OnlyActions: true
WithP1Deck: [SOR_246 SOR_200 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_246,SOR_200
## EXPECT
P1DISCARDCOUNT:3
P1DECKCOUNT:8
P1DECKTOPCARD:SEC_080
