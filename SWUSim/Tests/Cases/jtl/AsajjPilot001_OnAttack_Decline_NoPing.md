# JTL_001 pilot grant is "you may" — declining the friendly-unit pick does nothing (no self-ping,
# the enemy TIE survives).

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_001;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENACOUNT:1
