# SOR_013 Cassian Andor (deployed) — the draw is optional ("You may"). Cassian deploys and attacks
# P2's base; the reactive offers a draw, P1 declines (NO) → no card drawn (deck stays 1, hand stays 0).

## GIVEN
P1LeaderBase: SOR_013/SOR_024
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Deck: SOR_128

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:0
P1DECKCOUNT:1
P1LEADER:EPICUSED
