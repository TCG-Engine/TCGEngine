# SWUSim Replay Schema
Palpatine WhenDeployed — take control of a damaged non-leader unit (auto-resolve single target)

## GIVEN
P1LeaderBase: SOR_006/SOR_024
P2LeaderBase: SOR_007/SOR_024
SkipPreGame: true
WithP2GroundArena: SOR_095:1:2
WithP1Resources: 8

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2
