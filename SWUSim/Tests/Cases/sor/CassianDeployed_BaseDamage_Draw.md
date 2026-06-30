# SOR_013 Cassian Andor (deployed Leader Unit, 4/6) — "When you deal damage to an enemy base: You may
# draw a card." P1 deploys Cassian (6 resources) and attacks P2's base (Saboteur, 4 power). The base
# takes 4, and the reactive offers P1 a draw → YES → P1 draws 1 (deck 1 → 0, hand 0 → 1).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Deck: SOR_128

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:1
P1DECKCOUNT:0
P1LEADER:EPICUSED
