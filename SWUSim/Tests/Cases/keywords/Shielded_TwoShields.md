# Shielded: only 1 shield is consumed even when 2 are attached
# Crafty Smuggler has 2 Shield tokens pre-attached.
# Marine attacks: exactly 1 Shield is removed. Smuggler takes 0 damage.
# 1 Shield token remains after the attack.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_095:1:0   # Battlefield Marine 3/3
WithP2GroundArena: SOR_207:1:0   # Crafty Smuggler 2/2
WithP2GroundArenaUpgrade: 0:SOR_T02   # Shield 1
WithP2GroundArenaUpgrade: 0:SOR_T02   # Shield 2

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
