# OnAttackTakeCredit
#// LAW_221 Lieutenant Gorn (4/4) — On Attack: take control of an enemy Credit token. Attacks the base;
#// P2 loses its Credit, P1 gains one.

## GIVEN
CommonSetup: yyw/bgw/{theirResources:0}
P1OnlyActions: true
WithP2Credits: 1
WithP1GroundArena: LAW_221:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:0
