# LOF_108 Malakili — "If a friendly Creature unit would deal damage to a friendly unit, prevent that
# damage." Bendu (LOF_170, a Creature) attacks the P2 base; its On Attack deals 3 to each other unit.
# With Malakili in play, P1's own units (Malakili + SOR_046) take 0 — prevented — while the enemy
# SOR_046 takes the full 3.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_170:1:0
WithP1GroundArena: LOF_108:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:10
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:2:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3
