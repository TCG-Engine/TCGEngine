# SWUSim Replay Schema
Palpatine OnAttack — decline sacrifice, no bonus damage, normal combat

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
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:1:DAMAGE:3
