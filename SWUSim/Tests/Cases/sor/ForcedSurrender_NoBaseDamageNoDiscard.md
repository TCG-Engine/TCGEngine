# SOR_175 — gating guard: if you did NOT damage an opponent's base this phase, they do NOT discard.
# P1 plays SOR_175 with no prior base damage → draws 2, but P2's hand is untouched.

## GIVEN
CommonSetup: rrk/ggw/{myResources:7;handCardIds:SOR_175;theirHandCardIds:SOR_128,SOR_225}
P1OnlyActions: true
WithP1Deck: SOR_128
WithP1Deck: SOR_225

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:0
P2HANDCOUNT:2
P2DISCARDCOUNT:0
