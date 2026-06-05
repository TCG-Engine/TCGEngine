# SOR_137 on non-Force SOR_095: OnAttack condition fails — no damage to P2 unit.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_137
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
