# Choose1of1
#// SOR_125 Prepare for Takeoff — search top 8: choose 1 of 1 matching Vehicle unit.
#// COVERAGE: offer=SearchOffer_VehicleUnitsInTheTopEightOnly (decision left pending, pool read
#//           directly: a non-Vehicle, a Vehicle-trait UPGRADE and a Vehicle unit at depth 9 are all
#//           excluded, so the filter is proven to be trait AND card type AND depth, and arena-blind) ·
#//           control=PlayedFromOpponentsResources_SearchesYourOwnDeck (an event has no persistent
#//           object to change hands, so the reachable reading is a card played by someone other than
#//           its owner: played out of the OPPONENT's resources, "your deck" is still the PLAYER's
#//           deck, and the opponent's Vehicle-stocked deck is never searched) · decline=ChooseNoneof1
#//           + ChooseNoneof2 ("up to 2" includes zero: nothing drawn, whole window returned) ·
#//           boundary pair=the quantity ladder Choose1of1 / Choose1of2 / Choose1of3 / Choose2of2 /
#//           Choose2of3 pins the cap at 2 (never 3 from 3), and
#//           SearchOffer_VehicleUnitsInTheTopEightOnly pins the depth at 8 (card 9 is out of reach) ·
#//           reqboundary=every resolving section (play and search answer are separate serialized
#//           requests) and PlayedFromOpponentsResources_SearchesYourOwnDeck in particular, where the
#//           steal choice and the search each land in their own request.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_244

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:9

---

# Choose1of2
#// SOR_125 Prepare for Takeoff — search top 8: choose 1 of 2 matching Vehicle units.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_162
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_244

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:9

---

# Choose1of3
#// SOR_125 Prepare for Takeoff — search top 8: choose 1 of 3 matching Vehicle units.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_162
WithP1Deck: SOR_086
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_244

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:9

---

# Choose2of2
#// SOR_125 Prepare for Takeoff — search top 8: choose 2 of 2 matching Vehicle units.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_162
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_244,SOR_162

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:8

---

# Choose2of3
#// SOR_125 Prepare for Takeoff — search top 8: choose 2 of 3 matching Vehicle units.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_162
WithP1Deck: SOR_086
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_244,SOR_162

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:8

---

# ChooseNoneof1
#// SOR_125 Prepare for Takeoff — search top 8: choose none of 1 matching Vehicle unit.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:10

---

# ChooseNoneof2
#// SOR_125 Prepare for Takeoff — search top 8: choose none of 2 matching Vehicle units.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_162
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:10

---

# PlayedFromOpponentsResources_SearchesYourOwnDeck
#// SOR_125 Prepare for Takeoff — the CONTROL axis. An event has no persistent object to change hands,
#// so the reachable owner-vs-controller reading is a card PLAYED by someone other than its owner:
#// "search the top 8 cards of YOUR deck" must resolve for the player who played it. P2 has this very
#// event sitting in its resource row; P1 plays LAW_066 Tear This Ship Apart and plays it from there
#// for free. The search must run on P1's deck — P1's deck holds the only Snowspeeder, while P2's deck
#// is stocked with Disabling Fang Fighters (also Vehicle units) that must never be offered. P1 draws
#// the Snowspeeder, and the spent event goes to its OWNER's (P2's) discard while P2 refills the
#// resource from its own deck.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 13
WithP1Hand: LAW_066
WithP2Resources: 1:SOR_125:1
WithP1Deck: [SOR_244 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
WithP2Deck: [SOR_162 SOR_162 SOR_162]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0
- P1>AnswerDecision:SOR_244

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_244
P1DECKCOUNT:8
P2HANDCOUNT:0
P2DECKCOUNT:2
P2RESCOUNT:1
P2DISCARDCOUNT:1

---

# SearchOffer_VehicleUnitsInTheTopEightOnly
#// SOR_125 Prepare for Takeoff — the OFFER axis, and the two exclusions no answer-based section can
#// reach. Answering a search proves the branch, never the POOL, so the decision is left PENDING and
#// the pool is read directly. The top 8 is stacked with all three classes at once: two Vehicle UNITS
#// (a ground Snowspeeder and a space Alliance X-Wing, so the filter is proven arena-blind), a
#// non-Vehicle unit, and Grievous's Wheel Bike — an UPGRADE that carries the Vehicle trait, which
#// passes the trait test but fails "Vehicle UNITS" and must stay out. A fourth Vehicle unit sits at
#// depth 9, one card past the window, and must also stay out. Only the two Vehicle units inside the
#// top 8 are selectable, and nothing has been drawn.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: [SOR_244 SOR_237 TWI_236 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_162 SOR_162]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_244
P1SEARCHPLAYABLEHAS:SOR_237
P1SEARCHPLAYABLENOT:TWI_236
P1SEARCHPLAYABLENOT:SOR_063
P1SEARCHPLAYABLENOT:SOR_162
P1HANDCOUNT:0
