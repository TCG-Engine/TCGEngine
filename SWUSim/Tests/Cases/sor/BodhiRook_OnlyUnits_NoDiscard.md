# SOR_201 Bodhi Rook — non-unit filter guard: P2's hand is all units (SOR_095, SOR_128), so there
# is no valid non-unit card to discard → the discard fizzles (nothing leaves P2's hand). The "look
# at an opponent's hand" still happens, so Bodhi shows P2's hand as an acknowledge popup; after the
# OK no decision is left pending. Bodhi still enters play.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_201
WithP2Hand: SOR_095
WithP2Hand: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:OK

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P1NODECISION
LOGCONTAINS:looked at
