# LOF_002 Mother Talzin (deployed) — On Attack: may give a unit -1/-1. Deployed, she attacks the base; her
# On Attack drops SOR_046 to 2/6.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:LOF_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 5
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:2
