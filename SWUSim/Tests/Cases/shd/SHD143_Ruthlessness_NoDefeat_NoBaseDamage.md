# SHD_143 Ruthlessness — the base damage is gated on DEFEATING the defender. Host (5 power) attacks a
# SOR_046 (7 HP) that survives → no defeat, so no base damage.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_143
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2BASEDMG:0
