# Twin Suns Phase 2: two leaders, deploying LEADER 1 deploys ONLY leader 1 (index-threaded).
# Both are IBH_053 (deploy threshold 6). DeployLeader:1 must flip leader 1's Deployed flag, not leader 0's.

## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:IBH_053;
  myLeader2:IBH_053
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader:1

## EXPECT
P1LEADERCOUNT:2
P1LEADER0DEPLOYED:false
P1LEADER1DEPLOYED:true
