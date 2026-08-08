# Draw3_TopAndBottom
#// SEC_232 Kreia's Whispers (event, cost 2) — Draw 3 cards, then put a card from your hand on TOP of your
#//   deck and another on the BOTTOM. This test guards the draw-3 step (deck 5 → 2, hand 3); the two
#//   sequential hand→deck MZCHOOSE picks can't be driven by the in-process regression runner (event →
#//   draw → two consecutive same-player MZCHOOSE is the documented divergence) but are verified end-to-end
#//   in the live TestSchemaStep path: TOP choose (4 targets) → answer → BOTTOM choose (3 targets) → answer
#//   → pending:[] (final deck 4, hand 1).

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_232
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:2
P1HANDCOUNT:3

---

# DrawClampsToDeckSize_TwoLeft
#// SEC_232 Kreia's Whispers — "Draw 3" clamps to the cards available. With only 2 cards in the deck, P1
#//   draws 2 (deck 2 → 0). Hand goes from 2 (after playing the event) to 4. The subsequent TOP/BOTTOM
#//   placement is the documented two-consecutive-same-player MZCHOOSE that the in-process runner can't
#//   drive; this section guards the draw-clamp step only.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_232
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P1HANDCOUNT:4

---

# EmptyDeck_DrawsNothing
#// SEC_232 Kreia's Whispers — with an empty deck the "Draw 3" draws nothing (no deck-out damage from a
#//   card ability). After playing the event the hand still holds its 2 remaining cards; the deck stays 0.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_232
WithP1Hand: SOR_095
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P1HANDCOUNT:2


---

# InFlightEventIsNotAPlacementOption
#// SEC_232 Kreia's Whispers — REGRESSION GUARD. ActivateCard Removes the event to the discard BEFORE
#// dispatching its When Played, but `ZoneSearch("myHand", null)` still returns removed entries — so
#// Kreia's Whispers was offering ITSELF as a card to put on the deck. Picking it silently placed nothing.
#// With an empty deck and 2 other cards in hand, the TOP prompt must offer exactly those 2 (myHand-1 and
#// myHand-2; index 0 is the removed event). Same in-flight-event family as SEC_178 Pursue the Lead.
## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_232
WithP1Hand: SOR_095
WithP1Hand: SOR_046
## WHEN
- P1>PlayHand:0
## EXPECT
P1SELECTABLEEXACT:myHand-1&myHand-2

---

# EmptyDeck_PlacementStillHappens
#// SEC_232 Kreia's Whispers — "Draw 3 cards, THEN put a card from your hand on top of your deck and
#// another on the bottom." The "then" is sequential, not conditional: with an EMPTY deck nothing is drawn
#// but BOTH placements still resolve. P1 picks one card for the top; the other is the only remaining
#// option so the bottom placement auto-resolves. Hand empties (2 → 0) and the deck goes 0 → 2.
#// (EmptyDeck_DrawsNothing above only covers the draw half and leaves this prompt pending.)
## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_232
WithP1Hand: SOR_095
WithP1Hand: SOR_046
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
P1NODECISION

---

# EmptyDeck_SingleCardInHand_OnlyTheTopPlacement
#// SEC_232 Kreia's Whispers — with an empty deck and only ONE other card in hand, that card goes on TOP
#// and there is nothing left for the bottom placement; the ability finishes cleanly rather than hanging.
#// The single legal pick auto-resolves, so no answer is needed.
## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_232
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_095
P1NODECISION

---

# DrawClampsToDeckSize_OneLeft_PlacementStillTriggers
#// SEC_232 Kreia's Whispers — the one-card boundary between the two-card clamp and the empty-deck case.
#// With exactly 1 card in the deck, "draw 3" draws that 1 (deck 1 → 0) and hand goes from 2 (after the
#// event leaves it) to 3, then the top/bottom placement still runs on the resulting hand.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_232
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P1HANDCOUNT:3
