# JTL_012's grant is gated "If it's a Fighter". On a non-Fighter host (JTL_069, Capital Ship) the
# On Attack does NOT fire — no decision, the enemy TIE is undamaged.

## GIVEN
CommonSetup: yrk/grw/{myResources:6;myLeader:JTL_012;myLeaderDeployedPilot:true}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION
