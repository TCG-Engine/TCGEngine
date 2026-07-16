# VambraceGrappleshot_ExhaustDefender
#// SHD_074 Vambrace Grappleshot — attached unit gains "On Attack: Exhaust the defender." The host
#// (SOR_046 3/7 + SHD_074 +2/+2 = 5/9) attacks a ready SOR_046 (3/7): deals 5 (defender survives with 2)
#// and the granted On Attack exhausts the defender.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_074
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:5
