# OnAttackShieldNonUnique
#// LAW_095 Finn (6/5, Ambush) — On Attack: you may give a Shield token to a non-unique unit. Attacks the
#// base; shield the non-unique SEC_080.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_095:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
