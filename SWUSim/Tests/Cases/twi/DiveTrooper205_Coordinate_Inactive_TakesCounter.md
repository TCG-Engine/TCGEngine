# TWI_205 Clone Dive Trooper — with Coordinate inactive (it's the only friendly unit), no defender
# debuff: attacking the enemy Clone (2/2), the clone's full 2-power counter defeats the 1-HP Dive
# Trooper (both trade off).

## GIVEN
CommonSetup: yyk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_205:1:0
WithP2GroundArena: TWI_T02:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
