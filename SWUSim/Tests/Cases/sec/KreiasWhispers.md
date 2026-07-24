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
