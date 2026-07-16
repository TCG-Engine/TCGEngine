# OnAttackBuffDebuff
#// LAW_031 Bossk (3/5) — On Attack: give a unit +1/+1 for this phase; you may give a unit -1/-1 for this
#// phase. Bossk attacks the base; buff Bossk (+1/+1 -> 4/6), debuff enemy SOR_046 (-1/-1 -> 2/6).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_031:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_031
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6
