# LAW_112 Boonta Eve Flagbearer (1/3) — When a friendly unit attacks: if no other units have attacked
# this phase, heal 2 from your base. SOR_046 (the first attacker) attacks the base; P1's base (damaged
# 2) heals to 0.

## GIVEN
CommonSetup: bbw/bgw/{myBaseDamage:2}
P1OnlyActions: true
WithP1GroundArena: LAW_112:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1BASEDMG:0
