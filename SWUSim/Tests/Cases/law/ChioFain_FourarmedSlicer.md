# OnAttackBothDraw
#// LAW_048 Chio Fain (2/4) — On Attack: you may choose 2 players. If you do, they each draw a card.
#// (2-player: both players draw.)

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_048:1:0
WithP1Deck: SOR_237
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:1
