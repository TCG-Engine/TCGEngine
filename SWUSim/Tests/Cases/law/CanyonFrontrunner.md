# OnAttackDebuffIfFirst
#// LAW_228 Canyon Frontrunner (3/2) — On Attack: if no other units have attacked this phase (including
#// enemy units), you may give a unit -2/-0 for this phase. It's the only attacker -> debuff SOR_046
#// (3/7 -> 1/7).

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_228:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1
