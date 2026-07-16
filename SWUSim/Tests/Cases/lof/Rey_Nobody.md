# Deployed_WhenDeployed_DiscardHandDraw2
#// LOF_012 Rey — When Deployed: you may discard your hand. If you do, draw 2 cards. Rey deploys
#// (7 resources), discards her 2-card hand, draws 2 → hand 2, discard 2.

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

---

# NonUnitForceDeal1
#// LOF_012 Rey — Action [Exhaust]: If you played a non-unit Force card this phase, deal 1 damage to a unit.
#// P1 plays LOF_074 (a Force upgrade) onto Plo Koon, then the leader deals 1 to SOR_046.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LOF_012;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: LOF_074
WithP1Resources: 1
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
