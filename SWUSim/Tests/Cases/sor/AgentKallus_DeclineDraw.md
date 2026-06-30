# SOR_115 Agent Kallus — the draw is optional ("You may"): declining draws nothing. Kallus kills an
# enemy unique unit, the reactive offers a draw, P1 says NO → no card drawn.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP2GroundArena: SOR_079:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:1
P1HANDCOUNT:0
