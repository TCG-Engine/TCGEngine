# SHD_141 Kylo Ren — buffing a Villainy unit (SEC_080) grants only the +2/+0 phase buff, no Experience
# (→ 5 power, no subcard).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_141:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
