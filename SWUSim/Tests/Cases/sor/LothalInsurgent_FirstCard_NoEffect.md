# SOR_190 Lothal Insurgent — guard: if Lothal is the FIRST card played this phase, the "if you
# played another card this phase" condition fails → no opponent draw/discard. P2's hand and deck are
# untouched.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_190
WithP2Deck: SOR_171

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:0
P2DECKCOUNT:1
P2DISCARDCOUNT:0
