# myLeader override + myLeaderDeployedPilot: JTL_001 Asajj (pilot-capable leader) is deployed as a
# Pilot upgrade onto P1's first friendly unit (the Vehicle host SOR_225 in space). The host becomes
# a leader unit (CardLeaderCanDeployAsUpgrade), and the leader side reads Deployed.

## GIVEN
SkipPreGame: true
P1OnlyActions: true
CommonSetup: rrk/ggw/{myResources:6; myLeader:JTL_001; myLeaderDeployedPilot:1}
WithP1SpaceArena: SOR_225:1:0

## WHEN

## EXPECT
P1LEADER:DEPLOYED
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_001
P1SPACEARENAUNIT:0:ISLEADERUNIT
