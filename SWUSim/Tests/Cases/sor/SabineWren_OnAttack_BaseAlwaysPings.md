# SOR_142 Sabine Wren — when attacking a BASE, she always pings that base (no choice, the defender IS
# the base): 1 (ping) + 2 (combat) = 3 to the enemy base.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0

## WHEN
- P1>AttackGroundArena:0

## EXPECT
P2BASEDMG:3
P1NODECISION
