# Saboteur: unit can attack the base even when an enemy Sentinel is in play
# Rebel Pathfinder (SOR_239, Saboteur, 2/3) attacks P2 base.
# Echo Base Defender (SOR_098, Sentinel) is in P2 arena but must be bypassed.
# Pathfinder takes 0 damage (base doesn't counter-attack). P2 base takes 2.

## GIVEN
CommonSetup: yrw/yrw
WithP1GroundArena: SOR_239:1:0   # Rebel Pathfinder (Saboteur)
WithP2GroundArena: SOR_098:1:0   # Echo Base Defender (Sentinel)

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:2
