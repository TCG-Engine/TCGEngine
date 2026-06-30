## GIVEN
# myLeader:SOR_005 overrides the code's natural leader (rrk would be Vader SOR_010) with any cardID.
# Deployed as a unit to reveal the override on the board.
CommonSetup: rrk/grw/{myResources:5;myLeader:SOR_005;myLeaderDeployed:true}
P1OnlyActions: true

## WHEN

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_005
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1LEADER:DEPLOYED
