# AttackEndHealIfBase
#// LAW_046 Chirrut Îmwe (8/6, Saboteur) — When Attack Ends: if this unit dealt combat damage to a base,
#// you may heal 4 from another unit. Attacks the base; heal 4 from the damaged friendly SOR_046 (4 -> 0).

## GIVEN
CommonSetup: brw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_046:1:0
WithP1GroundArena: SOR_046:1:4

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:0
