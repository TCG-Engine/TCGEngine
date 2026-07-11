# SHD_141 Kylo Ren (6-cost 5/? ground) — "On Attack: Give a unit +2/+0 for this phase. If it's a non-Villainy
# unit, also give an Experience token to it." Kylo attacks the base and buffs the friendly non-Villainy
# SOR_046: +2/+0 (phase) AND +1/+1 (Experience) → 6/8, with 1 Experience subcard.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_141:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:8
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
