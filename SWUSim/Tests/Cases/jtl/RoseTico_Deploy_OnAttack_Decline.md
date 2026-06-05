# JTL_004 Rose Tico (deployed leader unit) — the On Attack heal is optional ("You may"). P1 deploys
# Rose, attacks P2's base, and DECLINES the heal (AnswerDecision:-): the X-Wing keeps its 2 damage.
# Proves the may-decline path.

## GIVEN
P1LeaderBase: JTL_004/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:2

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:2
P2BASEDMG:4
P1LEADER:DEPLOYED
