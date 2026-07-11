# SHD_045 Rose Tico (4-cost 2/6 ground) — Shielded + "On Attack: You may defeat a Shield token on a
# friendly unit. If you do, give 2 Experience tokens to that unit." Rose carries a Shield; on attacking the
# base she defeats her own Shield and gains 2 Experience (→ 4/8, Shield gone, 2 Exp subcards).

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_045:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_045
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:8
