# TWI_201 Aid from the Innocent — the search matches only Heroism NON-UNIT cards. With the top 10 holding
# a Heroism UNIT (SOR_095, excluded because it's a unit) and a non-Heroism event (TWI_176 Aggression,
# excluded by aspect) but NO Heroism non-unit card, nothing matches: nothing is discarded and all 10
# cards go to the bottom. (Only the event itself lands in the discard.)
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
