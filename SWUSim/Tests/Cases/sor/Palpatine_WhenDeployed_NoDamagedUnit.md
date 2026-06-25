# SWUSim Replay Schema
Palpatine WhenDeployed — no damaged units, no steal trigger fires

## GIVEN
CommonSetup: ggk/ggk/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 8

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1NODECISION
