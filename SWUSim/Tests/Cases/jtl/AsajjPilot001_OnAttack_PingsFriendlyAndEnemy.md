# JTL_001 Asajj Ventress deployed as a PILOT — the host gains "On Attack: You may deal 1 to a
# friendly unit; if you do, deal 1 to an enemy unit in the same arena." Host (SOR_237) attacks the
# base; the grant pings the host (only friendly) for 1, then the same-arena enemy TIE (2/1) dies.

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_001;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENACOUNT:0
