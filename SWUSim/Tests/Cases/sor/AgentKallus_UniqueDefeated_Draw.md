# SOR_115 Agent Kallus — "When another unique unit is defeated: You may draw a card." Kallus (4/4)
# attacks an enemy UNIQUE unit (SOR_079, 1/4) and defeats it → the reactive offers P1 a draw → YES →
# P1 draws 1. (Kallus takes 1 counter, survives.)

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_115:1:0
WithP2GroundArena: SOR_079:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:0
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:1
