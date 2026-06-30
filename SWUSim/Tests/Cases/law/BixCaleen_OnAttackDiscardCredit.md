# LAW_236 Bix Caleen (4/5) — When Played/On Attack: you may discard a card from your hand. If you do,
# create a Credit token. Attacks the base; discard SOR_237 -> 1 Credit.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_236:1:0
WithP1Hand: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0

## EXPECT
P1CREDITCOUNT:1
P1HANDCOUNT:0
P1DISCARDCOUNT:1
