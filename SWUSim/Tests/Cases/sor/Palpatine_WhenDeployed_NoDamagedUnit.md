# SWUSim Replay Schema
Palpatine WhenDeployed — no damaged units, no steal trigger fires

## GIVEN
P1LeaderBase: SOR_006/SOR_024
P2LeaderBase: SOR_007/SOR_024
SkipPreGame: true
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 8

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1NODECISION
