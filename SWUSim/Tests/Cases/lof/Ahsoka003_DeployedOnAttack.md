# LOF_003 Ahsoka Tano (deployed) — On Attack: may give a friendly unit Sentinel. She attacks the base and
# grants herself Sentinel.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:LOF_003;
  myBase:SOR_021;
  theirBase:SOR_021
}
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
