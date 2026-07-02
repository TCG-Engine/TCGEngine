# JTL_012 deployed as a PILOT on a Fighter host (SOR_237) — the host gains "On Attack: You may deal
# 3 damage to a unit." Host attacks the base; deals 3 to the enemy JTL_069 (4/7 -> 3 damage).

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
