# LAW_018 Lando Calrissian (deployed) — "When Deployed: You may defeat a friendly Credit token. If you
# do, create 3 Credit tokens." Deploy Lando with 1 existing Credit; defeat it and create 3 → net 3
# Credits.

## GIVEN
P1LeaderBase: LAW_018/SOR_028
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Credits: 1

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:YES

## EXPECT
P1CREDITCOUNT:3
