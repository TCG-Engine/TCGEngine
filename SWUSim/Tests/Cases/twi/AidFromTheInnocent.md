# DiscardsTwoHeroismNonUnits
#// TWI_201 Aid from the Innocent (Event, cost 5, Cunning/Heroism) — "Search the top 10 cards of your deck
#// for 2 Heroism non-unit cards and discard them. (Put the other cards on the bottom of your deck in a
#// random order.)" Top 10 hold two Heroism events (SOR_246, SOR_200) + 8 fillers; both are discarded and
#// the 8 fillers go to the bottom.
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

---

# FilterExcludesUnitsAndOffAspect
#// TWI_201 Aid from the Innocent — the search matches only Heroism NON-UNIT cards. With the top 10 holding
#// a Heroism UNIT (SOR_095, excluded because it's a unit) and a non-Heroism event (TWI_176 Aggression,
#// excluded by aspect) but NO Heroism non-unit card, nothing matches: nothing is discarded and all 10
#// cards go to the bottom. (Only the event itself lands in the discard.)
## GIVEN
CommonSetup: yyw/rrk/{myResources:5;handCardIds:TWI_201}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 TWI_176 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:
## EXPECT
P1DISCARDCOUNT:1
P1DECKCOUNT:10
