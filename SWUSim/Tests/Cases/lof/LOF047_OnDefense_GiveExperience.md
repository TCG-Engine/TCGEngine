# LOF_047 — On Defense (when this unit is attacked, before damage): you may give an Experience token to
# this unit. P1's SOR_046 (3/7) attacks LOF_047 (3/4); before damage P2 gives it an Experience token →
# 4/5. It then takes 3 (survives, DAMAGE:3) and counters for 4 (SOR_046 DAMAGE:4), proving the On
# Defense reaction resolved before combat damage.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_047:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:5
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:4
