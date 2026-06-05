# SOR_006 Emperor Palpatine — Epic Action: "If you control 8 or more resources, deploy
# this leader." With only 7 resources the condition is unmet, so DeployLeader is a no-op:
# the leader stays in the leader zone, ready, with the epic action still available.

## GIVEN
P1LeaderBase: SOR_006/SOR_024
P2LeaderBase: SOR_007/SOR_024
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
P1LEADER:READY
P1GROUNDARENACOUNT:0
P1NODECISION
