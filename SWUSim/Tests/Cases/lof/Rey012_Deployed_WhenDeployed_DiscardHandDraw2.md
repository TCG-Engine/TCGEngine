# LOF_012 Rey — When Deployed: you may discard your hand. If you do, draw 2 cards. Rey deploys
# (7 resources), discards her 2-card hand, draws 2 → hand 2, discard 2.

## GIVEN
CommonSetup: grw/brk/{
  myLeader:LOF_012
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Hand: SOR_095
WithP1Hand: SEC_080
WithP1Deck: SOR_237
WithP1Deck: SOR_225
WithP1Deck: SOR_046

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:2
P1DECKCOUNT:1
