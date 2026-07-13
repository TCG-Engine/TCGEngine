# TWI_034 General Grievous — "On Attack: If this unit has 4 or more Lightsaber upgrades attached to him,
# defeat 4 enemy units." Grievous carries 4 Lightsabers (TWI_248, SOR_053, TWI_152, LOF_090 — all pure
# stat/When-Played on a non-Force host, so no extra On-Attack triggers) and attacks the base → his On
# Attack defeats all 4 enemy units (4 present → all defeated).
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_034:1:0
WithP1GroundArenaUpgrade: 0:TWI_248
WithP1GroundArenaUpgrade: 0:SOR_053
WithP1GroundArenaUpgrade: 0:TWI_152
WithP1GroundArenaUpgrade: 0:LOF_090
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0 SOR_128:1:0 LAW_180:1:0]
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENACOUNT:0
