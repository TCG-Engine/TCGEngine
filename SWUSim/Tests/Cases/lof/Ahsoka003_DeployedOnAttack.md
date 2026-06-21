# LOF_003 Ahsoka Tano (deployed) — On Attack: may give a friendly unit Sentinel. She attacks the base and
# grants herself Sentinel.

## GIVEN
P1LeaderBase: LOF_003/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
