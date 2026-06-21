# LAW_258 Criminal Contact (1/4) — On Attack: you may pay 2 resources. If you do, create a Credit
# token. Attacks the base; pay 2 -> 1 Credit.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: LAW_258:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1CREDITCOUNT:1
P1RESAVAILABLE:0
