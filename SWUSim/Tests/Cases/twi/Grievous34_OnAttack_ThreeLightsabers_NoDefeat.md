# TWI_034 General Grievous — the On Attack mass-defeat needs 4+ Lightsabers. With only 3 Lightsabers
# attached, attacking does NOT defeat any enemy units (the gate fails): all 4 enemies remain.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_034:1:0
WithP1GroundArenaUpgrade: 0:TWI_248
WithP1GroundArenaUpgrade: 0:SOR_053
WithP1GroundArenaUpgrade: 0:TWI_152
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0 SOR_128:1:0 LAW_180:1:0]
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENACOUNT:4
