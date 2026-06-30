# JTL_001 Asajj Ventress — No friendly Vehicle present.
# With no eligible Vehicle, DeployLeader skips the Unit/Pilot choice and
# deploys normally to the Ground Arena (no OPTIONCHOOSE, no decision pending).
# Deploy threshold = 6.

## GIVEN
CommonSetup: gbk/gbk/{
  myLeader:JTL_001;
  myBase:SOR_022;
  theirLeader:JTL_001;
  theirBase:SOR_022
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_001
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1NODECISION
