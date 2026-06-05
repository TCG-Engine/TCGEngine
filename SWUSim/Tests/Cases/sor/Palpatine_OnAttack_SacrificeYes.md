# SWUSim Replay Schema
Palpatine OnAttack — sacrifice friendly unit, deal 1 damage, proceed to combat

## GIVEN
P1LeaderBase: SOR_006/SOR_024
P2LeaderBase: SOR_007/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 8

## WHEN
- P1>DeployLeader
- P2>Pass
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_006
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:1
