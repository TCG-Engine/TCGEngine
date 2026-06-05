# JTL_191 Invincible — absence guard: the deploy-leader bounce only fires while you control Invincible.
# With no Invincible in play, deploying the leader offers no decision and P2's cost-3 unit is untouched.

## GIVEN
P1LeaderBase: SOR_015/SOR_021
P2LeaderBase: SOR_005/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:1
P1NODECISION
