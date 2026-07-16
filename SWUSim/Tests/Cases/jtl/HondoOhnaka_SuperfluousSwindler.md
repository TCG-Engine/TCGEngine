# OnAttack_MoveUpgrade
#// JTL_056 Hondo Ohnaka — Shielded + "On Attack: You may take control of a non-Pilot upgrade on a unit
#// and attach it to a different eligible unit." Hondo attacks the base; on attack he takes SOR_120
#// (Academy Training, +2/+2) off the enemy SOR_046 and reattaches it to the friendly SOR_095.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_056:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_120
P2BASEDMG:3
