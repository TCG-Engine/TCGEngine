# JTL_008 Wedge deployed as a PILOT — the host gains "On Attack: The next Pilot card you play this
# phase costs 1 less (includes Piloting costs)." After the host attacks (arming the discount), P1
# plays JTL_046 (a Pilot, cost 2) AS A UNIT for 1: 10 ready resources -> 9.

## GIVEN
CommonSetup: bgw/rrk/{myResources:10;myLeader:JTL_008;myLeaderDeployedPilot:true;myhandCardIds:JTL_046}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:Unit

## EXPECT
P1RESAVAILABLE:9
