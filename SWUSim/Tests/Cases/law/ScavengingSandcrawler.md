# OnAttackDiscardToBottomCredit
#// LAW_238 Scavenging Sandcrawler (1/7) — On Attack: you may put a card from your discard on the bottom
#// of your deck. If you do, create a Credit token. Attacks the base; put SOR_237 on the bottom -> 1 Credit.

## GIVEN
CommonSetup: yyk/bgw/{discardCardIds:SOR_237}
P1OnlyActions: true
WithP1GroundArena: LAW_238:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1CREDITCOUNT:1
P1DISCARDCOUNT:0
P1DECKCOUNT:1
