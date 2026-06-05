# SOR_238 C-3PO — On Attack window (same ability fires when C-3PO attacks). C-3PO (in play, ready,
# power 1) attacks P2's base; the On Attack trigger resolves first: choose 2 (matches SOR_095) →
# Draw → SOR_095 drawn (deck 3→2, hand 0→1). Then combat deals C-3PO's 1 power to P2's base.

## GIVEN
CommonSetup: ggw/ggw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_238:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:2
- P1>AnswerDecision:Draw

## EXPECT
P2BASEDMG:1
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DECKCOUNT:2
P1GROUNDARENACOUNT:1
