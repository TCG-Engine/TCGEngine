# JTL_001 Asajj Ventress — No friendly Vehicle present.
# With no eligible Vehicle, DeployLeader skips the Unit/Pilot choice and
# deploys normally to the Ground Arena (no OPTIONCHOOSE, no decision pending).
# Deploy threshold = 6.

## GIVEN
P1LeaderBase: JTL_001/SOR_022
P2LeaderBase: JTL_001/SOR_022
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
