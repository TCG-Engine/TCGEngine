# LAW_037 Han Solo (1/1, Shielded) — On Attack: give an Experience token to this unit. He attacks the
# base; the OnAttack Exp makes him 2/2.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_037:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_037
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
