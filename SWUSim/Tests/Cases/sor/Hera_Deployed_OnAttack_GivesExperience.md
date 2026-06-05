# SOR_008 Hera (deployed Leader Unit, 4/6) — "On Attack: You may give an Experience token to another
# unique unit." P1 deploys Hera (6 resources) and attacks the base; On Attack, she gives an Experience
# token to the other unique unit (Zeb, in space → UPGRADECOUNT 1). Her 4 power hits the base.

## GIVEN
P1LeaderBase: SOR_008/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_146:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1LEADER:DEPLOYED
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:4
