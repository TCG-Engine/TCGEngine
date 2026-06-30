# LOF_170 Bendu — On Attack: deal 3 damage to each other unit. Bendu attacks the base; the friendly and
# enemy 3/7 units each take 3, and Bendu itself is unaffected.

## GIVEN
CommonSetup: rrw/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_170:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3
