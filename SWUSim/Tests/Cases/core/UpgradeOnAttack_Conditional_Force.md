# SOR_137 on Yoda (Force unit): conditional OnAttack fires — deal 1 to each P2 ground unit.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_045:1:0
WithP1GroundArenaUpgrade: 0:SOR_137
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
