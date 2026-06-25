# LOF_260 The Father — When you use the Force: You may deal 1 damage to this unit. If you do, the Force is
# with you. P1 uses Mother Talzin's Force action; The Father's reaction fires first — P1 deals 1 to The
# Father and regains the Force. Then Talzin's -1/-1 resolves on SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:LOF_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_260:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1HASFORCE
P2GROUNDARENAUNIT:0:POWER:2
