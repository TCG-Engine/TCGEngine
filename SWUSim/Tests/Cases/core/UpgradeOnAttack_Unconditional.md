# JTL_172 on SOR_165 (Vehicle, no innate OnAttack): upgrade OnAttack fires unconditionally, deals 1 damage to P2 unit.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_165:1:0
WithP1GroundArenaUpgrade: 0:JTL_172
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
