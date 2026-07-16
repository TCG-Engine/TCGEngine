# OnAttackSpaceDebuffGroundBuff
#// LAW_068 Millennium Falcon (2/5, space) — On Attack: you may give a space unit -2/-0 for this phase;
#// you may give a ground unit +2/+0 for this phase. Debuff enemy SOR_237 (2/3 -> 0/3), buff friendly
#// SEC_080 (3/3 -> 5/3).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_068:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2SPACEARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:5
