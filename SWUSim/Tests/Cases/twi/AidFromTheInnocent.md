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

---

# DiscardedCardsArePlayableThisPhaseForTwoLess
#// TWI_201 Aid from the Innocent — the SECOND sentence: "For this phase, you may play the discarded cards,
#// and they each cost 2 resources less." It had no implementation at all — the two cards were discarded and
#// then simply sat there. P1 pays 5 for the event with exactly 5 resources, leaving ZERO ready, and can
#// still play the discarded SOR_199 Bamboozle (cost 2 - 2 = 0) and have it resolve: SOR_120 leaves the
#// unit and lands in P1's hand. Zero ready resources is what makes the discount load-bearing here.
## GIVEN
CommonSetup: yyw/rrk/{myResources:5;handCardIds:TWI_201}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1Deck: [SOR_199 SOR_141 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_199
- P1>PlayFromDiscard:1
## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:2

---

# DiscardedCards_PermissionExpiresNextPhase
#// TWI_201 Aid from the Innocent — "For THIS PHASE". The permission (and its 2-resource discount) must not
#// survive into the next action phase. P1 discards SOR_199 with the search but does NOT play it; both
#// players pass through regroup, and on the next action phase the attempt is a no-op — Bamboozle stays in
#// the discard, the unit keeps its upgrade and P1's resources are untouched.
## GIVEN
CommonSetup: yyw/rrk/{myResources:5;handCardIds:TWI_201}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1Deck: [SOR_199 SOR_141 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_199
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayFromDiscard:1
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:2
