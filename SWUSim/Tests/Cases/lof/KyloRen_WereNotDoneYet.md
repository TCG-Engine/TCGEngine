# Deployed_WhenDeployed_DeclineNoUpgrade
#// LOF_001 Kylo Ren — When Deployed is a "may" loop; declining ('-') the first offer plays nothing.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:LOF_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Discard: SOR_120

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1

---

# Deployed_WhenDeployed_PlayUpgradeFromDiscard
#// LOF_001 Kylo Ren — When Deployed: play any number of upgrades from your discard on this unit,
#// paying their costs. Kylo deploys (7 resources), then plays Academy Training (SOR_120, cost 2)
#// from the discard onto himself → 1 upgrade on Kylo, discard empty, 5 resources left.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:LOF_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Discard: SOR_120

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1DISCARDCOUNT:0
P1RESAVAILABLE:5

---

# DiscardUpgradeDraw
#// LOF_001 Kylo Ren — Action [Exhaust]: Discard a card from your hand. If you discarded an upgrade this way,
#// draw a card. P1 discards SOR_053 (an upgrade) and draws SOR_059; the leader exhausts.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:LOF_001;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_053
WithP1Deck: SOR_059

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1LEADER:EXHAUSTED
