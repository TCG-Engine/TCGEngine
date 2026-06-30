# LOF_086 Drengir Spawn (3/3) — Overwhelm + "attacks and defeats a unit: give it Experience tokens equal
# to the defeated unit's cost." It attacks a pre-damaged cost-3 Sentinel (2 power), defeats it (taking 2
# counter), and gains 3 Experience tokens.

## GIVEN
CommonSetup: ggk/rrw
P1OnlyActions: true
WithP1GroundArena: LOF_086:1:0
WithP2GroundArena: SOR_063:1:2

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
