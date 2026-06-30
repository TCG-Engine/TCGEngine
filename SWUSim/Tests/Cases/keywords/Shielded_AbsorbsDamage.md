# Shielded: shield token absorbs all combat damage
# Crafty Smuggler (SOR_207, 2/2) has a Shield token pre-attached.
# Battlefield Marine (3/3) attacks it. Shield absorbs damage: Smuggler takes 0.
# Shield is defeated (removed). Marine takes 2 damage (Smuggler's 2 power).

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_095:1:0   # Battlefield Marine 3/3
WithP2GroundArena: SOR_207:1:0   # Crafty Smuggler 2/2
WithP2GroundArenaUpgrade: 0:SOR_T02   # Shield on Smuggler

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
