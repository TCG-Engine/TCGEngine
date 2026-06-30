# SEC_219 Ebon Hawk — reveal nothing → neither bonus applies; base takes the plain 3.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_219:1:0
WithP1Hand: SEC_148

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1NODECISION
