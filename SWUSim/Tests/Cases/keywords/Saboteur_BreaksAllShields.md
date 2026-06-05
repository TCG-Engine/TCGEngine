# Saboteur: breaks all shields on the defender before combat
# Rebel Pathfinder (SOR_239, Saboteur, 2/3) attacks Crafty Smuggler (2/2) which has a Shield.
# Saboteur removes the shield before damage. Combat proceeds without shield protection.
# Pathfinder (2 power) kills Smuggler (2 HP). Smuggler (2 power) deals 2 to Pathfinder.
# Smuggler goes to discard.

## GIVEN
CommonSetup: yrw/yrw
WithP1GroundArena: SOR_239:1:0   # Rebel Pathfinder (Saboteur)
WithP2GroundArena: SOR_207:1:0   # Crafty Smuggler 2/2
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
