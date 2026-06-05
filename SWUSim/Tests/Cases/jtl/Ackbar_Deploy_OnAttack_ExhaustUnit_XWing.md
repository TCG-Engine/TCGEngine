# JTL_016 Admiral Ackbar (deployed leader unit) — On Attack: You may exhaust a unit. If you do, its
# controller creates an X-Wing token. P1 deploys Ackbar (control 6+ resources), attacks P2's base, and
# on attack exhausts the enemy SOR_095, so P2 creates an X-Wing.

## GIVEN
P1LeaderBase: JTL_016/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_T02
P2BASEDMG:3
P1LEADER:DEPLOYED
