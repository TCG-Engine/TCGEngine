# LOF_063 Oggdo Bogdo (5/5) — "When this unit attacks and defeats a unit: heal 2 damage from this unit."
# The damaged Oggdo (1 damage) attacks and defeats the enemy 3/1, takes 3 counter (→4 damage), then heals
# 2 (→2 damage).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_063:1:1
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2
