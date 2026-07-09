# SHD_104 Inspiring Mentor — attached unit gains "On Attack: Give an Experience token to another
# friendly unit." The host (SOR_046 + SHD_104 = 4 power) attacks the base; its On Attack gives an
# Experience token to the only other friendly unit (SOR_095 3/3 → 4/4).

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_104
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:1:POWER:4
