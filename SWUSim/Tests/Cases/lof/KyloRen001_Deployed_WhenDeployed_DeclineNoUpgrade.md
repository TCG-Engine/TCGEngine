# LOF_001 Kylo Ren — When Deployed is a "may" loop; declining ('-') the first offer plays nothing.

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
