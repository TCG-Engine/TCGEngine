# SEC_197 Furtive Handmaiden (Ground, 2/2) — On Attack: you may discard a card from your hand. If you
#   do, draw a card. SEC_197 attacks P2's base; P1 discards SOR_095 and draws the top of deck.

## GIVEN
CommonSetup: yyw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_197:1:0
WithP1Hand: SOR_095
WithP1Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:2
P1HANDCOUNT:1
P1DISCARDCOUNT:1
