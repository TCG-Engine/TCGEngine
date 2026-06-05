# Shielded: shield is removed even when it's not the first upgrade in the list
# Crafty Smuggler (2/2 base) has Experience (+1/+1 → 3/3) and Shield, in that order.
# K-2SO (SOR_145, 2/4) attacks. Shield absorbs damage (Smuggler takes 0).
# K-2SO takes 3 damage (Smuggler's 3 power with Experience).
# After: Experience token remains; Shield is gone.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_145:1:0   # K-2SO 2/4
WithP2GroundArena: SOR_207:1:0   # Crafty Smuggler 2/2
WithP2GroundArenaUpgrade: 0:SOR_T01   # Experience token (index 0)
WithP2GroundArenaUpgrade: 0:SOR_T02   # Shield token (index 1)

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
