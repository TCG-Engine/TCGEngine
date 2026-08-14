# SearchUnitDraw
#// SOR_123 Recruit (Event, cost 1) — Search the top 5 of your deck for a unit, reveal it, and
#// draw it (rest to the bottom). The top 5 contain one unit (Battlefield Marine SOR_095) among
#// non-unit (event) fillers; the player picks it and draws it. Recruit itself goes to discard.
#// COVERAGE: offer=SearchFilter_NonUnitPickIsRejected (the search picker is not an mzID target
#//           choose, so its pool can't be asserted pending; the unit-only restriction is instead
#//           proven behaviorally — an event answer is rejected server-side) + MultipleUnits_PickSecond
#//           (both legal units acceptable) · reqboundary=SearchUnitDraw (peek → pick crosses a
#//           serialized decision boundary; the finalize resolves against the stored peek set) ·
#//           control=N/A (own-deck search; no unit state or seat-crossing involved) · boundary pair=
#//           DeckOfThreeCards_StillSearches (deck < 5) + EmptyDeck_PlaysToNoEffect (deck = 0) vs the
#//           full-deck sections · decline=TakeNothing_AllFiveToBottom (find is optional; '-' bottoms
#//           all five) + NoUnitInTopFive (nothing selectable, forced decline)

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_123
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:7
P1DISCARDCOUNT:1

---

# TakeNothing_AllFiveToBottom
#// SOR_123 Recruit — the find is optional: declining ('-') draws nothing and puts all five peeked
#// cards on the bottom of the deck (random order). A distinct 6th card (SOR_222) seeded below the
#// top five becomes the new top, proving the peeked five were bottomed. Deck size is unchanged.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_123
WithP1Deck: [SOR_095 SOR_171 SOR_171 SOR_171 SOR_171 SOR_222 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:7
P1DECKTOPCARD:SOR_222
P1DISCARDCOUNT:1

---

# DeckOfThreeCards_StillSearches
#// SOR_123 Recruit — with fewer than 5 cards in the deck the search looks at what's there: a 3-card
#// deck offers its single unit, which is drawn; the other two cards go to the bottom (deck 3 → 2).

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_123
WithP1Deck: [SOR_095 SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DECKCOUNT:2

---

# EmptyDeck_PlaysToNoEffect
#// SOR_123 Recruit — with an EMPTY deck the event still plays: it goes to discard, its cost is
#// spent, nothing is drawn and no choice is raised.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_123

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# NoUnitInTopFive
#// SOR_123 Recruit — when none of the top five cards is a unit there is nothing selectable: nothing
#// is drawn and all five go to the bottom (the distinct 6th card SOR_222 becomes the new top).

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_123
WithP1Deck: [SOR_171 SOR_216 SOR_220 SOR_251 SOR_078 SOR_222 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:7
P1DECKTOPCARD:SOR_222
P1DISCARDCOUNT:1

---

# SearchFilter_NonUnitPickIsRejected
#// SOR_123 Recruit — the search is restricted to UNITS server-side, not just in the picker UI:
#// answering the search with a peeked EVENT (SOR_251, an illegal pick) draws nothing — the illegal
#// choice is dropped and every peeked card is bottomed (distinct 6th card SOR_222 becomes the top,
#// deck size unchanged). Intended: the filter must hold even when both legal units are present.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_123
WithP1Deck: [SOR_228 SOR_251 SOR_216 SOR_220 SOR_229 SOR_222 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_251

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:7
P1DECKTOPCARD:SOR_222
P1DISCARDCOUNT:1

---

# MultipleUnits_PickSecond
#// SOR_123 Recruit — either offered unit may be taken, not just the first peeked: picking the 5th
#// card (SOR_229 Cell Block Guard) draws it; the other four peeked cards are bottomed and the
#// distinct 6th card SOR_222 becomes the new top (deck 7 → 6).

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_123
WithP1Deck: [SOR_228 SOR_251 SOR_216 SOR_220 SOR_229 SOR_222 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_229

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_229
P1DECKCOUNT:6
P1DECKTOPCARD:SOR_222
