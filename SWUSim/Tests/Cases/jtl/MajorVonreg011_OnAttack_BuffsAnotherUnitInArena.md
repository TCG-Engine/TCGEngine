# JTL_011 Major Vonreg deployed as a PILOT — the host gains "On Attack: You may give another unit in
# this arena +1/+0 for this phase." Host (SOR_225 @0) attacks the base; buffs the other friendly
# space unit JTL_069 (4/7 -> 5/7).

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_011;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: [SOR_225:1:0 JTL_069:1:0]

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1SPACEARENAUNIT:1:POWER:5
