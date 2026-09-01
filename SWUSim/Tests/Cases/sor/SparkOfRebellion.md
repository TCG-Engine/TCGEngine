# DiscardChosen
#// SOR_200 Spark of Rebellion (Event, cost 2, Cunning/Heroism) — "Look at an opponent's hand and
#// discard a card from it." P1 plays Spark and sees P2's two-card hand; P1 chooses to discard the
#// first card (SOR_171, an event). P2 hand 2→1, P2 discard 0→1 (From HAND). The Spark event itself
#// goes to P1's discard.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_200
WithP2Hand: SOR_171
WithP2Hand: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0

## EXPECT
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND
P1DISCARDCOUNT:1

---

# SingleCardHand_StillShowsTheHand
#// SOR_200 Spark of Rebellion — "LOOK AT an opponent's hand and discard a card from it." Two clauses; the
#// look is not conditional on the discard being a choice.
#// With exactly ONE card in the opponent's hand the discard auto-resolves, so no MZCHOOSE over
#// `theirHand` is raised — and that MZCHOOSE is the only thing that reveals a Visibility=Self hand. The
#// player was shown nothing and simply told a card had gone. SWUOfferDiscard now presents the hand
#// explicitly in that case (default-on for from=opp since 2026-08-18); this section pins it by leaving
#// the popup pending.
#// The unfiltered callers were the easiest to miss: they only lose the hand on a 1-card hand, whereas a
#// FILTERED one (Jam Communications, Tip the Scale, Charged with Espionage) loses it on any board with
#// ≤1 matching card. Same bug, different frequency.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_200
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Opponent's_hand
P2HANDCOUNT:0
P2DISCARDCOUNT:1

---

# Offer_IsExactlyTheOpponentHand
#// SOR_200 Spark of Rebellion — "discard a card FROM IT" (the opponent's hand), so the pool is every
#// card the opponent holds and nothing else: no type filter (an event, a unit and another unit are all
#// selectable), and none of P1's OWN hand cards, even though P1 is the one choosing. P1 holds an Open
#// Fire alongside the Spark to prove the pool never crosses back. The pick is left PENDING so the exact
#// pool can be read.
#// COVERAGE: offer=Offer_IsExactlyTheOpponentHand (pending exact pool = the whole opponent hand, no
#//           friendly cards) · decline=N/A (no "you may" and the pool is a PUBLIC-to-the-chooser reveal,
#//           not a play from hand; the discard is mandatory once a card exists) ·
#//           boundary=Offer_IsExactlyTheOpponentHand (3 cards → 3 offered) vs
#//           SingleCardHand_StillShowsTheHand (1 card → auto-resolves, hand still revealed) vs
#//           EmptyOpponentHand_NothingToDiscard (0 cards → no reveal, no discard) ·
#//           control=N/A (the event reads a zone named by "an opponent", never "your"; no unit and no
#//           persistent state that a control change could re-seat) ·
#//           reqboundary=SimulateRequestBoundary_ChoiceSurvivesTheRoundTrip

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: [SOR_200 SOR_172]
WithP2Hand: [SOR_171 SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirHand-0&theirHand-1&theirHand-2

---

# EmptyOpponentHand_NothingToDiscard
#// SOR_200 Spark of Rebellion — the no-valid-target cell. With the opponent holding NOTHING there is no
#// hand to look at and no card to discard, so the event resolves to nothing: P2's hand and discard both
#// stay empty and no decision is raised. The Spark itself is still played and still costs its 2
#// resources (an action that fizzles still pays), landing in P1's discard.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_200

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_200
P1RESAVAILABLE:0

---

# SimulateRequestBoundary_ChoiceSurvivesTheRoundTrip
#// SOR_200 Spark of Rebellion — in production the "discard a card from it" pick ends the request, so
#// the chosen hand mzID arrives in a FRESH process and the pending look-at-hand context must live in
#// the serialized gamestate rather than in memory. Mirrors Offer_IsExactlyTheOpponentHand with the
#// boundary inserted before the answer: P1 still discards the opponent's Battlefield Marine, the other
#// two cards stay in hand, and the discard is recorded as coming FROM HAND.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: [SOR_200 SOR_172]
WithP2Hand: [SOR_171 SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirHand-1

## EXPECT
P2HANDCOUNT:2
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095
P2DISCARDUNIT:0:FROM:HAND
P1DISCARDCOUNT:1
