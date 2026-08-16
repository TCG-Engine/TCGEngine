# SearchTraitCard
#// SHD_127 Commission (1-cost event) — "Search the top 10 cards of your deck for a Bounty Hunter,
#// Item, or Transport card, reveal it, and draw it." Deck: two non-matching fillers + the Transport
#// (SHD_065 Vigilant Pursuit Craft, Vehicle/Transport). It's drawn; the rest go to the bottom.
#// COVERAGE: offer=SearchOffer_ExactlyTheThreeMatchingArms (the top-10 search is a card-ID display, not
#//           an mzID target choice, so SELECTABLE* cannot address it — SEARCHPLAYABLEHAS/NOT asserts the
#//           pending search's playable set instead, with all three filter arms present and every
#//           non-matching card absent) + IneligiblePick_BehavesAsTakeNothing (the same legal set proved
#//           by its EFFECT: an out-of-filter answer draws nothing) · reqboundary=
#//           RequestBoundary_LegalPickStillResolves + RequestBoundary_IneligiblePickStillRejected (the
#//           search's stored filter is re-read from serialized state after the boundary) ·
#//           control=N/A (an event has no persistent object and resolves only for the player who plays
#//           it — there is nothing whose control could change; the Smuggle path in Smuggle_SearchAndDraw
#//           is the only alternate dispatch) · boundary pair=TenthCardIsWithinReach (match at depth 10 ->
#//           offered and drawn) vs EleventhCardIsOutOfReach (same match one card deeper -> unreachable,
#//           no prompt, nothing drawn), plus DeckSmallerThanSearchDepth_StillFindsTheMatch and
#//           EmptyDeck_CleanNoOp at the low end · decline=TakeNothing_AllTenCardsToBottom (explicit
#//           decline with three legal finds on the table) + NoValidOptions_TakeNothingReturnsAllTen
#//           (nothing legal to take at all, offer asserted empty in NoValidOptions_OfferIsEmpty)

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SHD_065 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_065

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
P1DISCARDCOUNT:1

---

# SearchOffer_ExactlyTheThreeMatchingArms
#// SHD_127 — the filter is a THREE-WAY test (Bounty Hunter OR Item OR Transport) and it is the
#// load-bearing half of the card. The top 10 hold one card for each arm — SOR_204 Greedo
#// (Underworld/Bounty Hunter), SOR_071 Electrostaff (Item/Weapon) and SOR_110 Frontline Shuttle
#// (Vehicle/Transport) — mixed among seven cards that match NONE of them: SOR_095 / SEC_080 (plain
#// Ground units), SOR_171 (an Event), SOR_128 (Ground unit) and SOR_046 (Ground unit). The section stops
#// on the pending search so the OFFER itself is what is asserted: exactly the three arms are playable
#// and every non-matching card is withheld.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SOR_204 SEC_080 SOR_071 SOR_171 SOR_110 SOR_128 SOR_046 SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_204
P1SEARCHPLAYABLEHAS:SOR_071
P1SEARCHPLAYABLEHAS:SOR_110
P1SEARCHPLAYABLENOT:SOR_095
P1SEARCHPLAYABLENOT:SEC_080
P1SEARCHPLAYABLENOT:SOR_171
P1SEARCHPLAYABLENOT:SOR_128
P1SEARCHPLAYABLENOT:SOR_046
P1HANDCOUNT:0

---

# BountyHunterArm_RevealedAndDrawn
#// SHD_127 — the Bounty Hunter arm of the filter, resolved. SOR_204 Greedo (Underworld/Bounty Hunter)
#// sits between two non-matching Ground units; P1 picks him, he is drawn to hand and the other two go
#// to the bottom (deck stays at 2). Commission itself lands in the discard.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SOR_204 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_204

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_204
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1NODECISION

---

# ItemArm_RevealedAndDrawn
#// SHD_127 — the Item arm of the filter, resolved. SOR_071 Electrostaff (Item/Weapon) is an UPGRADE,
#// not a unit: the filter is a trait test, not a type test, so it is a legal find. It is drawn to hand
#// and the two non-matching Ground units go to the bottom.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SOR_071 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_071

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_071
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1NODECISION

---

# IneligiblePick_BehavesAsTakeNothing
#// SHD_127 — the trait filter is server-enforced, not a client hint. The top 3 are deliberately
#// discriminating: SOR_095 Battlefield Marine (no matching trait), SOR_204 Greedo (the only legal pick)
#// and SEC_080 Imperial Dark Trooper (no matching trait). P1 answers with the INELIGIBLE SOR_095: it is
#// dropped, nothing is drawn, and all three peeked cards go back to the bottom (deck stays at 3).
#// Intended: an ineligible answer must behave exactly like "take nothing".

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SOR_204 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
P1DISCARDCOUNT:1
P1NODECISION

---

# TakeNothing_AllTenCardsToBottom
#// SHD_127 — the decline branch. The same discriminating top 10 as
#// SearchOffer_ExactlyTheThreeMatchingArms (three legal finds available), but P1 declines the pick with
#// the choose-nothing token. Nothing is drawn: the hand stays empty, all 10 peeked cards return to the
#// deck and the deck count is unchanged. Per the card's reminder text the returned cards are in a RANDOM
#// order, so only the COUNT is asserted, never a specific ordering.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SOR_204 SEC_080 SOR_071 SOR_171 SOR_110 SOR_128 SOR_046 SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:10
P1DISCARDCOUNT:1
P1NODECISION

---

# NoValidOptions_OfferIsEmpty
#// SHD_127 — a full top 10 with NOT ONE Bounty Hunter, Item or Transport among them. The search still
#// runs and still reveals the ten cards, but its playable set is empty: none of SOR_095, SEC_080,
#// SOR_171, SOR_128 or SOR_046 may be taken. Left pending so the empty OFFER is the assertion. This is
#// the negative twin of SearchOffer_ExactlyTheThreeMatchingArms — an empty match list means "nothing is
#// legal", never "everything is legal".

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SEC_080 SOR_171 SOR_128 SOR_046 SOR_095 SEC_080 SOR_171 SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLENOT:SOR_095
P1SEARCHPLAYABLENOT:SEC_080
P1SEARCHPLAYABLENOT:SOR_171
P1SEARCHPLAYABLENOT:SOR_128
P1SEARCHPLAYABLENOT:SOR_046
P1HANDCOUNT:0

---

# NoValidOptions_TakeNothingReturnsAllTen
#// Resolution half of NoValidOptions_OfferIsEmpty. With nothing legal to take, P1 takes nothing: all ten
#// revealed cards go back to the bottom of the deck, the hand stays empty and the deck count is
#// unchanged. Commission is still spent — it sits in the discard and its 1 resource stays paid.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SEC_080 SOR_171 SOR_128 SOR_046 SOR_095 SEC_080 SOR_171 SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:10
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# DeckSmallerThanSearchDepth_StillFindsTheMatch
#// SHD_127 — "the top 10 cards" is a maximum, not a requirement. With only TWO cards left in the deck
#// the search still runs over what is there: SOR_071 Electrostaff (Item) is found and drawn, and the one
#// non-matching card returns to the bottom, leaving a deck of 1. Low-end partner of
#// TenthCardIsWithinReach / EleventhCardIsOutOfReach.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_071 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_071

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_071
P1DECKCOUNT:1
P1DISCARDCOUNT:1
P1NODECISION

---

# TenthCardIsWithinReach
#// SHD_127 — the depth boundary, inclusive half. SOR_071 Electrostaff (Item) is the TENTH card of an
#// 11-card deck: it is exactly at the search depth, so it is offered and drawn. The 9 non-matching cards
#// above it go to the bottom and the 11th card is never touched, leaving a deck of 10. Boundary partner
#// of EleventhCardIsOutOfReach, which buries the identical card one card deeper.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SEC_080 SOR_171 SOR_128 SOR_046 SOR_095 SEC_080 SOR_171 SOR_128 SOR_071 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_071

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_071
P1DECKCOUNT:10
P1DISCARDCOUNT:1
P1NODECISION

---

# EleventhCardIsOutOfReach
#// SHD_127 — the depth boundary, exclusive half. Identical fixture to TenthCardIsWithinReach except
#// SOR_071 Electrostaff is the ELEVENTH card: one card past the search depth. It matches the filter
#// perfectly and is still unreachable — the offer is empty, P1 must take nothing, and the hand stays
#// empty with the deck back at 11. Depth is enforced, not merely suggested.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SEC_080 SOR_171 SOR_128 SOR_046 SOR_095 SEC_080 SOR_171 SOR_128 SOR_046 SOR_071]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_071

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:11
P1DISCARDCOUNT:1
P1NODECISION

---

# EmptyDeck_CleanNoOp
#// SHD_127 — searching an EMPTY deck is a clean no-op, not an error and not a stuck decision. P1 plays
#// Commission with nothing at all in the deck: the event is still played (it costs its 1 resource and
#// goes to the discard), no search prompt is raised, no card is drawn and the deck stays empty.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_127
P1RESAVAILABLE:0
P1NODECISION

---

# RequestBoundary_LegalPickStillResolves
#// SHD_127 — the search spans two requests in production (reveal the top 10, then pick), so everything
#// it needs must live in serialized state rather than in a transient global. Same fixture as
#// BountyHunterArm_RevealedAndDrawn with a fresh-process boundary inserted between the play and the
#// pick: SOR_204 Greedo is still drawn and the other two cards still go to the bottom.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SOR_204 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_204

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_204
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1NODECISION

---

# RequestBoundary_IneligiblePickStillRejected
#// SHD_127 — the sharper half: the trait FILTER itself has to survive the request boundary, because the
#// answer is validated in a later request than the one that built the offer. Same fixture as
#// IneligiblePick_BehavesAsTakeNothing with the boundary inserted before the ineligible answer — a
#// filter cached in memory would be gone by then and would let SOR_095 through. It is still rejected:
#// nothing is drawn and all three cards go back to the bottom.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_127
WithP1Deck: [SOR_095 SOR_204 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
P1DISCARDCOUNT:1
P1NODECISION

---

# Smuggle_SearchAndDraw
#// SHD_127 — "Smuggle [3 resources, Command]" is a SECOND DISPATCH PATH into the same ability. P1 plays
#// Commission out of its RESOURCES rather than its hand, paying the 3-resource Smuggle cost (Command is
#// covered by the Command base + Command leader, so there is no aspect penalty). The identical search
#// runs: SOR_204 Greedo is drawn and Commission ends up in the discard exactly as on the hand path, with
#// 3 of the 4 ready resources spent. The deck count is deliberately NOT asserted here: the smuggled
#// slot's replacement card and the search both come off the top of the same deck, so the count depends
#// on which of the two runs first — the payment and the search RESULT are what this path is proving.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1Resources: 1:SHD_127:0,4:SOR_095:1
WithP1Deck: [SOR_095 SOR_204 SEC_080]

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:SOR_204

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_204
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_127
P1RESAVAILABLE:1
P1NODECISION

---

# Smuggle_SlotIsRefilledBeforeTheSearch_ShortDeck
#// CR 8.22.g — "as it enters play, a card played using Smuggle is replaced in the resource zone by the
#// top card of a player's deck; the two cards are considered to enter play SIMULTANEOUSLY." So the refill
#// happens BEFORE Commission's search resolves, and the search only sees what is left. With a 3-card
#// deck: SOR_095 refills the resource slot, then the search looks at the remaining 2 cards and draws
#// SOR_071. The resource COUNT staying at 5 is the load-bearing assertion — refilling after the ability
#// instead would let the search consume the whole deck first, leaving the slot permanently unfilled and
#// costing the player a resource outright.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1Resources: 4:SOR_095:1,1:SHD_127:1
WithP1Deck: [SOR_095 SOR_071 SOR_046]

## WHEN
- P1>SmuggleResource:4
- P1>AnswerDecision:SOR_071

## EXPECT
P1RESCOUNT:5
P1HANDCOUNT:1
P1HANDCARD:0:SOR_071
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_127
