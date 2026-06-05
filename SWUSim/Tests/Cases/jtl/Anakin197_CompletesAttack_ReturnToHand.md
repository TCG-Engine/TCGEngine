# JTL_197 Anakin Skywalker — Piloting + "When attached unit completes an attack (and survives): You may
# return this upgrade to its owner's hand." JTL_068 (3/5 Vehicle) carries Anakin (+2/+3 pilot → 5 power),
# attacks the P2 base for 5, survives, then P1 returns Anakin to hand.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_068:1:0
WithP1GroundArenaUpgrade: 0:JTL_197

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1
