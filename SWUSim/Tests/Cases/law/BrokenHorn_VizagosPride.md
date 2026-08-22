# WhenPlayedDrawAndResource
#// LAW_083 Broken Horn (5/4) — When Played: if you have fewer cards in hand than an opponent, draw a
#// card; if you control fewer resources than an opponent, resource the top card of your deck. P1 ends
#// with fewer of both (hand 0 vs 3, resources 5 vs 6) -> draw 1 AND resource 1.

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:6}
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP2Hand: SEC_080
WithP1Deck: SOR_237
WithP1Deck: SOR_095
WithP1Hand: LAW_083

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESCOUNT:6
P1DECKCOUNT:0

---

# EmptyDeckDrawDealsBaseDamage
#// LAW_083 Broken Horn — with fewer cards in hand than the opponent the draw fires, but the deck is
#// empty so drawing from it deals 3 damage to P1's base. Fewer resources too, but the resource step
#// also finds an empty deck and does nothing.

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:6}
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP2Hand: SEC_080
WithP1Hand: SOR_095
WithP1Hand: LAW_083

## WHEN
- P1>PlayHand:1

## EXPECT
P1HANDCOUNT:1
P1RESCOUNT:5
P1DECKCOUNT:0
P1BASEDMG:3

---

# OneCardDeckDrawThenNoResource
#// LAW_083 Broken Horn — fewer hand and fewer resources, deck has exactly one card. The draw takes that
#// card, emptying the deck; the resource step then finds nothing to resource.

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:6}
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP2Hand: SEC_080
WithP1Deck: SOR_237
WithP1Hand: SOR_095
WithP1Hand: LAW_083

## WHEN
- P1>PlayHand:1

## EXPECT
P1HANDCOUNT:2
P1RESCOUNT:5
P1DECKCOUNT:0
P1BASEDMG:0

---

# NotFewerHandNoDrawButResource
#// LAW_083 Broken Horn — P1 does NOT have fewer cards in hand after playing it (2 vs 2), so no draw;
#// but P1 controls fewer resources, so the top card of the deck is resourced.

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:6}
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP1Deck: SOR_237
WithP1Hand: SOR_095
WithP1Hand: SEC_080
WithP1Hand: LAW_083

## WHEN
- P1>PlayHand:2

## EXPECT
P1HANDCOUNT:2
P1RESCOUNT:6
P1DECKCOUNT:0
P1BASEDMG:0

---

# FewerHandDrawButEqualResourcesNoResource
#// LAW_083 Broken Horn — fewer cards in hand than the opponent, so draw; resources are EQUAL (not
#// fewer), so no resource. Deck of two cards loses one to the draw.

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:5}
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP1Deck: SOR_237
WithP1Deck: SEC_080
WithP1Hand: SOR_095
WithP1Hand: LAW_083

## WHEN
- P1>PlayHand:1

## EXPECT
P1HANDCOUNT:2
P1RESCOUNT:5
P1DECKCOUNT:1
P1BASEDMG:0

---

# StolenResourceCountsTowardYouControl
#// COVERAGE: control=StolenResourceCountsTowardYouControl ("you control fewer resources" counts CONTROLLED
#//           resources, incl. one P1 controls but P2 owns) + PlayedByP2_ComparisonsRunFromP2Seat +
#//           PlayedByP2_MoreThanOpponent_NoEffect ("you"/"an opponent" resolve from the PLAYING seat, not
#//           a hardcoded P1; both seats' decks/hands are distinguishable) · offer=N/A (no chooser — both
#//           clauses are automatic conditionals) · decline=N/A (mandatory, no "you may") ·
#//           reqboundary=N/A (no decision, so no state is re-read across a request).
#//
#// LAW_083 Broken Horn — "if you CONTROL fewer resources than an opponent". One of P1's six resources is
#// a SOR_095 that P2 OWNS (the end state after an enemy card is resourced), so P1 controls 6 and P2
#// controls 6: EQUAL, not fewer, so nothing is resourced. Counting only P1-OWNED resources would read 5
#// vs 6 and wrongly resource the top of P1's deck. Hands are equal (1 vs 1) so the draw clause is silent
#// too, which makes P1's deck a clean single witness: it must still hold its one card.

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:6}
P1OnlyActions: true
WithP1ResourceControlled: SOR_095:2
WithP1Deck: SOR_237
WithP2Deck: [SOR_225 SOR_225]
WithP1Hand: [LAW_083 SOR_095]
WithP2Hand: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:6
P1DECKCOUNT:1
P1HANDCOUNT:1
P2RESCOUNT:6
P2DECKCOUNT:2
P2HANDCOUNT:1

---

# PlayedByP2_ComparisonsRunFromP2Seat
#// LAW_083 Broken Horn played by P2 — "you" is the ability's controller, so both comparisons must run
#// from P2's seat with P1 as "an opponent". P2 ends with 0 cards in hand vs P1's 3 (fewer → draw) and 5
#// resources vs P1's 6 (fewer → resource the top of P2's deck). The two seats are deliberately lopsided
#// the OTHER way from every P1 fixture in this file: an implementation that read P1-has-fewer would do
#// nothing here. Only P2's deck (2 → 0), hand (0 → 1 after the draw) and resources (5 → 6) move; P1's
#// deck, hand and resources are asserted unchanged.

## GIVEN
CommonSetup: bgw/ryk/{myResources:6;theirResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Deck: [SOR_225 SOR_225 SOR_225]
WithP2Deck: [SOR_237 SOR_095]
WithP1Hand: [SOR_095 SOR_237 SEC_080]
WithP2Hand: LAW_083

## WHEN
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2RESCOUNT:6
P2DECKCOUNT:0
P1HANDCOUNT:3
P1RESCOUNT:6
P1DECKCOUNT:3

---

# PlayedByP2_MoreThanOpponent_NoEffect
#// LAW_083 Broken Horn — the discriminating mirror of the section above. P2 again plays it, but now P2 is
#// AHEAD on both axes: 2 cards in hand vs P1's 1, and 6 resources vs P1's 3. Neither clause may fire.
#// Because P1 is the seat that is "fewer" on both counts, any implementation that compares from the wrong
#// seat (or hardcodes P1 as "you") would draw/resource here — and it must not touch P1's zones either, so
#// P1's hand and deck are asserted unchanged as well.

## GIVEN
CommonSetup: bgw/ryk/{myResources:3;theirResources:6}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Deck: [SOR_225 SOR_225 SOR_225]
WithP2Deck: [SOR_237 SOR_095]
WithP1Hand: SOR_095
WithP2Hand: [LAW_083 SOR_237 SOR_095]

## WHEN
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:2
P2RESCOUNT:6
P2DECKCOUNT:2
P1HANDCOUNT:1
P1RESCOUNT:3
P1DECKCOUNT:3

---

# TwinSuns_TheTwoComparisonsAreSatisfiedByDIFFERENTOpponents
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, DETERMINED). LAW_083 was filed under PROMPT (47) by
#// the text-based inventory and that was WRONG: "fewer cards in hand than AN opponent" is an EXISTENTIAL
#// CONDITION, not a target. Nothing downstream needs to know which opponent — you just draw/resource. A
#// picker here would raise a prompt Premier must never see.
#//
#// ⚠⚠ AND IT CARRIES A SECOND, SUBTLER DEFECT THAT ONLY FOUR SEATS EXPOSES: the card has TWO INDEPENDENT
#// comparisons (hand size, then resource count) and the old code aimed BOTH at the same single
#// OtherPlayer(). That silently COUPLED two tests which the card states separately — they may legitimately
#// be satisfied by DIFFERENT opponents, and at two seats that difference cannot exist.
#//
#// This section is built precisely on that split:
#//   • P1 (after playing Broken Horn) holds 1 card and controls 5 resources.
#//   • SEAT 2 — the seat the old code looked at — holds 0 cards and 4 resources, so it satisfies NEITHER.
#//   • SEAT 3 holds 3 cards  ⇒ satisfies the HAND clause only.
#//   • SEAT 4 controls 6 resources ⇒ satisfies the RESOURCE clause only.
#// Both clauses must therefore fire: P1 draws (hand 1→2, deck 2→1) and resources the top card
#// (deck 1→0, resources 5→6).
#// Under the old code seat 2 satisfies neither, so NOTHING happens — hand 1, deck 2, resources 5.
#// ⚠ A 2-player version CANNOT FAIL, and cannot even express the bug: with one opponent there is only one
#//   seat for both comparisons to point at. The seat count IS the test.
#// Mutation check: revert either max() to OtherPlayer() and this reds while all eight 2-player sections
#// above stay green.

## GIVEN
CommonSetup: ryk/bgw/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Resources: 5
WithP2Resources: 4
WithP3Resources: 4
WithP4Resources: 6
WithP1Hand: [LAW_083 SOR_095]
WithP1Deck: [SOR_237 SOR_237]
WithP3Hand: [SOR_095 SOR_237 SEC_080]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:2
P1DECKCOUNT:0
P1RESCOUNT:6
