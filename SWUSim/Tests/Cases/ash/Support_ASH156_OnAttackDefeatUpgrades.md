# ASH_156 R5-D4 (Ground, 3/4, Support) — On Attack: defeat all upgrades on the defending unit. R5-D4
# attacks SOR_046 (3/7) carrying SOR_120 (+2/+2 → 5/9); the On Attack defeats SOR_120 (back to 3/7), then
# combat deals 3. SOR_046 survives at UPGRADECOUNT 0.
## GIVEN
CommonSetup: rrw/rrw
WithP1GroundArena: ASH_156:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
