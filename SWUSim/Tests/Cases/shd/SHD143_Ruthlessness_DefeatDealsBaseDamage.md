# SHD_143 Ruthlessness — attached unit gains "When this unit attacks and defeats a unit: Deal 2 damage
# to the defending player's base." Host (SOR_046 3/7 + SHD_143 +2/+0 = 5 power) attacks and defeats
# SHD_095 (2/3); the grant then deals 2 to P2's base.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_143
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
