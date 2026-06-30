# LAW_181 Cloud-Rider Veteran (1/4) — On Attack: deal 2 damage to a base. Attacks the base: 1 (combat)
# + 2 (ability) = 3.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_181:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
