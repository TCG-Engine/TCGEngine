# SEC_232 Kreia's Whispers (event, cost 2) — Draw 3 cards, then put a card from your hand on TOP of your
#   deck and another on the BOTTOM. This test guards the draw-3 step (deck 5 → 2, hand 3); the two
#   sequential hand→deck MZCHOOSE picks can't be driven by the in-process regression runner (event →
#   draw → two consecutive same-player MZCHOOSE is the documented divergence) but are verified end-to-end
#   in the live TestSchemaStep path: TOP choose (4 targets) → answer → BOTTOM choose (3 targets) → answer
#   → pending:[] (final deck 4, hand 1).

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
