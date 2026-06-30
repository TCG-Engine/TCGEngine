# SOR_016 Grand Admiral Thrawn — Deploy: leader becomes 3/9 ground unit. Deploy is free (6 resources stay available).

## GIVEN
CommonSetup: yyk/grw/{myResources:6}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_016
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:9
P1RESCOUNT:6
P1RESAVAILABLE:6
