# ASH_062 The Mandalorian — the rider is "another friendly unit", so when The Mandalorian ITSELF is
# attacked the rider does NOT fire (no spurious prevention offer). Its own Shielded simply absorbs the
# damage: P1's SOR_046 attacks ASH_062, the Shield absorbs 3 (DAMAGE 0, SHIELDCOUNT 0), and ASH_062's
# 5 power counters onto SOR_046.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: ASH_062:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:ASH_062
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:5
