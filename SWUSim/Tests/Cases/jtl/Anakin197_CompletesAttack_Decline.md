# JTL_197 Anakin Skywalker — the return is optional. Declining (AnswerDecision:NO) leaves Anakin
# attached to SOR_095.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_068:1:0
WithP1GroundArenaUpgrade: 0:JTL_197

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:0
