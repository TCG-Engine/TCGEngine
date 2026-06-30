# LOF_001 Kylo Ren — When Deployed: play any number of upgrades from your discard on this unit,
# paying their costs. Kylo deploys (7 resources), then plays Academy Training (SOR_120, cost 2)
# from the discard onto himself → 1 upgrade on Kylo, discard empty, 5 resources left.

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
