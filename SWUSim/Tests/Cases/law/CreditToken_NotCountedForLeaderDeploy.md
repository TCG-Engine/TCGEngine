# Credit tokens are NOT resources — they do not count toward a leader's deploy threshold (CR 3.13).
#   P1 controls Luke (SOR_005, deploy cost 6) with only 5 real resources + 3 Credit tokens. Total real
#   resources (5) is below 6, so the deploy is unavailable even though 5+3=8 entries sit in the zone.
#   Proves credits give ramp for paying costs but never earlier leader deployment.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5}
P1OnlyActions: true
WithP1Credits: 3

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:READY
P1GROUNDARENACOUNT:0
P1CREDITCOUNT:3
