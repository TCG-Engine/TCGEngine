# LOF_015 Cal Kestis (deployed) — On Attack: an opponent chooses a ready unit they control; exhaust it. He
# attacks the base; P2 picks SOR_046 from its two ready units to be exhausted.

## GIVEN
P1LeaderBase: LOF_015/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 4
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
