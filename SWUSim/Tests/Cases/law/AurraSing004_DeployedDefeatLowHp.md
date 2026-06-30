# LAW_004 Aurra Sing (deployed) — "When Deployed: You may defeat a non-leader unit with 5 or less
# remaining HP." Deploy Aurra (7+ resources); the only eligible enemy is SOR_128 (3/1, 1 HP) — SOR_046
# (3/7) is NOT eligible. P1 defeats SOR_128, leaving SOR_046.

## GIVEN
CommonSetup: ybk/grw/{
  myLeader:LAW_004;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
