# SOR_045 Yoda — choosing "Opponent" → only P2 draws a card; P1 draws nothing.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_095
WithP2Deck: SEC_080

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Opponent

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:1
