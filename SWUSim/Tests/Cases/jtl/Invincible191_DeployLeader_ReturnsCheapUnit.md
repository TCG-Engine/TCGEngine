# JTL_191 Invincible — "When you deploy a leader: You may return a non-leader unit that costs 3 or
# less to its owner's hand." P1 controls Invincible (space) and deploys its leader; the only ≤3
# non-leader unit is P2's cost-3 SOR_063 Cloud City Wing Guard, which returns to P2's hand.
# (SOR_015 is a non-pilot leader, so deploying with a friendly Vehicle present offers no Unit/Pilot choice.)

## GIVEN
P1LeaderBase: SOR_015/SOR_021
P2LeaderBase: SOR_005/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: JTL_191:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
