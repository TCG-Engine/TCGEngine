# SOR_013 Cassian Andor (deployed) — "Use this ability only once each round." Two enemy-base hits in
# the same round; Cassian's reactive draws only for the FIRST. P1 deploys Cassian (ground) and has an
# Alliance X-Wing (SOR_237) in space; Cassian attacks P2's base (4) → draw (YES), then the X-Wing
# attacks P2's base (2) → no second offer. P1 drew exactly 1 (deck 2 → 1, hand 1), base took 4+2=6.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:SOR_013;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_237

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:6
P1HANDCOUNT:1
P1DECKCOUNT:1
P1LEADER:EPICUSED
