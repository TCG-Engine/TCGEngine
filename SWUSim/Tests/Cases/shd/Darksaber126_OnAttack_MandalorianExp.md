# SHD_126 The Darksaber — the attached unit gains "On Attack: give an Experience token to each OTHER
# friendly Mandalorian unit." SHD_034 (Mandalorian, wearing the Darksaber) attacks an enemy unit; the
# other friendly Mandalorian SOR_142 (2 power) gains an Experience token → 3 power. (The host is excluded.)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_034:1:0
WithP1GroundArenaUpgrade: 0:SHD_126
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
