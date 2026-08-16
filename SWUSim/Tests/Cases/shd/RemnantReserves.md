# SearchTop5_Draw3Units
#// SHD_093 Remnant Reserves (4-cost Command/Villainy event) — "Search the top 5 cards of your deck for
#// up to 3 units, reveal them, and draw them." Top 5 = 3 units (SOR_046, SOR_095, SOR_164) + 2 events
#// (SOR_171). P1 picks all 3 units → drawn to hand; the 2 events go to the bottom. Hand +3, deck -3.
#// COVERAGE: offer=SearchOffer_OnlyUnitsArePlayable (the top-5 search is a card-ID display, not an mzID
#//           target choice, so SELECTABLE* cannot address it — SEARCHPLAYABLEHAS/NOT reads the pending
#//           search's playable set instead, proving both non-unit types, an Event and an Upgrade, are
#//           withheld) + IneligiblePick_BehavesAsTakeNothing (the same legal set proved by its EFFECT) ·
#//           reqboundary=RequestBoundary_FilterAndCountSurvive (the stored filter AND the up-to-3 cap are
#//           re-read from serialized state after the boundary) · control=N/A (an event has no persistent
#//           object and the search resolves only for the player who played it — there is nothing whose
#//           control could change) · boundary pair=FifthCardIsWithinReach (a unit at depth 5 -> offered and
#//           drawn) vs SixthCardIsOutOfReach (the same unit one card deeper -> unreachable), plus the count
#//           cap OverCap_OnlyTheFirstThreeAreDrawn, the under-supply FewerLegalCardsThanTheCap and the low
#//           end DeckSmallerThanSearchDepth_StillFindsTheMatch / EmptyDeck_CleanNoOp ·
#//           decline=TakeNothing_AllFiveToBottom (the "up to 3" soft pass is an amount of ZERO taken with
#//           three legal finds on the table — the TARGET choice itself is never declinable)

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_164
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046,SOR_095,SOR_164

## EXPECT
P1HANDCOUNT:3
P1DECKCOUNT:2

---

# SearchOffer_OnlyUnitsArePlayable
#// SHD_093 — the filter is a card-TYPE test ("up to 3 units") and it is the load-bearing half of the card.
#// The top 5 hold three units (SOR_046 Consular Security Force, SOR_095 Battlefield Marine, SOR_164 Wampa)
#// interleaved with the two non-unit types that could be mistaken for one: SOR_171 Mission Briefing (an
#// Event) and SOR_069 Resilient (an Upgrade). The section stops on the pending search so the OFFER itself
#// is the assertion — all three units playable, both non-units withheld.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093
WithP1Deck: [SOR_046 SOR_171 SOR_095 SOR_069 SOR_164]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_046
P1SEARCHPLAYABLEHAS:SOR_095
P1SEARCHPLAYABLEHAS:SOR_164
P1SEARCHPLAYABLENOT:SOR_171
P1SEARCHPLAYABLENOT:SOR_069
P1HANDCOUNT:0

---

# PickOnlyOne_OtherFourToBottom
#// SHD_093 — "up to 3" includes ONE. From the same discriminating top 5 as
#// SearchOffer_OnlyUnitsArePlayable, P1 takes only SOR_046 and leaves the other two units behind: hand 1,
#// and all four unpicked cards return to the bottom so the deck is back to 4. Per the card's reminder text
#// they return in a RANDOM order, so only the COUNT is asserted.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093
WithP1Deck: [SOR_046 SOR_171 SOR_095 SOR_069 SOR_164]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_046
P1DECKCOUNT:4
P1DISCARDCOUNT:1
P1NODECISION

---

# TakeNothing_AllFiveToBottom
#// SHD_093 — the soft-pass branch of "up to 3". Three legal units are sitting in the top 5 and P1 takes
#// NONE of them: the amount taken is zero, nothing is drawn, and all five revealed cards return to the
#// bottom with the count unchanged at 5. The event is still spent — it sits in the discard and its 4
#// resources stay paid.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093
WithP1Deck: [SOR_046 SOR_171 SOR_095 SOR_069 SOR_164]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:5
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# IneligiblePick_BehavesAsTakeNothing
#// SHD_093 — the type filter is server-enforced, not a client hint. P1 answers with SOR_171 Mission
#// Briefing (an Event) and SOR_069 Resilient (an Upgrade), neither of which is a unit. Both are dropped:
#// nothing is drawn and all five peeked cards go back to the bottom, exactly as if P1 had taken nothing.
#// Intended: an ineligible answer must behave exactly like "take nothing".

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093
WithP1Deck: [SOR_046 SOR_171 SOR_095 SOR_069 SOR_164]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_171,SOR_069

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:5
P1DISCARDCOUNT:1
P1NODECISION

---

# FewerLegalCardsThanTheCap
#// SHD_093 — the cap is "up to 3", but only what is actually there can be taken. This top 5 holds just TWO
#// units (SOR_046, SOR_095) among three non-units; P1 takes both and there is no third to take. Hand 2,
#// the remaining three cards go to the bottom, deck 3. Proves the count offered caps at what is available
#// rather than forcing the player toward the printed 3.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093
WithP1Deck: [SOR_046 SOR_171 SOR_069 SOR_171 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046,SOR_095

## EXPECT
P1HANDCOUNT:2
P1HANDCARD:0:SOR_046
P1HANDCARD:1:SOR_095
P1DECKCOUNT:3
P1DISCARDCOUNT:1
P1NODECISION

---

# OverCap_OnlyTheFirstThreeAreDrawn
#// SHD_093 — "up to 3" is a hard CAP, enforced server-side in the order the picks were made. The top 5 are
#// FOUR units plus one event and P1 answers with all four units: the first three are drawn and the fourth
#// overflows into the same disposition as an unpicked card — the bottom of the deck. Hand 3, deck 2.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093
WithP1Deck: [SOR_046 SOR_095 SOR_164 SOR_128 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046,SOR_095,SOR_164,SOR_128

## EXPECT
P1HANDCOUNT:3
P1HANDCARD:0:SOR_046
P1HANDCARD:1:SOR_095
P1HANDCARD:2:SOR_164
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1NODECISION

---

# FifthCardIsWithinReach
#// SHD_093 — the depth boundary, inclusive half. SOR_046 is the FIFTH card of a 6-card deck: exactly at
#// the search depth, so it is offered and drawn. The 4 non-matching cards above it go to the bottom and
#// the 6th card is never touched, leaving a deck of 5. Boundary partner of SixthCardIsOutOfReach, which
#// buries the identical unit one card deeper.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093
WithP1Deck: [SOR_171 SOR_069 SOR_171 SOR_069 SOR_046 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_046
P1DECKCOUNT:5
P1DISCARDCOUNT:1
P1NODECISION

---

# SixthCardIsOutOfReach
#// SHD_093 — the depth boundary, exclusive half. Identical fixture to FifthCardIsWithinReach except
#// SOR_046 is the SIXTH card: one card past the search depth. It is a unit and it is still unreachable —
#// the offer holds nothing, the answer is dropped, and the hand stays empty with the deck back at 6. Depth
#// is enforced, not merely suggested.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093
WithP1Deck: [SOR_171 SOR_069 SOR_171 SOR_069 SOR_171 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:6
P1DISCARDCOUNT:1
P1NODECISION

---

# DeckSmallerThanSearchDepth_StillFindsTheMatch
#// SHD_093 — "the top 5 cards" is a maximum, not a requirement. With only TWO cards left the search runs
#// over what is there: SOR_046 is found and drawn and the one non-matching card returns to the bottom,
#// leaving a deck of 1. Low-end partner of FifthCardIsWithinReach / SixthCardIsOutOfReach.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093
WithP1Deck: [SOR_046 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_046
P1DECKCOUNT:1
P1DISCARDCOUNT:1
P1NODECISION

---

# EmptyDeck_CleanNoOp
#// SHD_093 — searching an EMPTY deck is a clean no-op, not an error and not a stuck decision. P1 plays the
#// event with nothing at all in the deck: it still costs its 4 resources and still goes to the discard, no
#// search prompt is raised, nothing is drawn and the deck stays empty.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_093
P1RESAVAILABLE:0
P1NODECISION

---

# RequestBoundary_FilterAndCountSurvive
#// SHD_093 — the search spans two requests in production (reveal the top 5, then pick), so the stored
#// filter AND the up-to-3 cap must live in serialized state rather than in a transient global. The
#// discriminating top 5 with a fresh-process boundary inserted between the play and the pick, and a
#// deliberately mixed answer: two ineligible non-units (SOR_171, SOR_069) and three legal units. A filter
#// lost across the boundary would let the non-units through and the cap would then bite at three of the
#// wrong cards. Exactly the three units are drawn and the two non-units go to the bottom.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093
WithP1Deck: [SOR_046 SOR_171 SOR_095 SOR_069 SOR_164]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:SOR_171,SOR_046,SOR_069,SOR_095,SOR_164

## EXPECT
P1HANDCOUNT:3
P1HANDCARD:0:SOR_046
P1HANDCARD:1:SOR_095
P1HANDCARD:2:SOR_164
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1NODECISION
