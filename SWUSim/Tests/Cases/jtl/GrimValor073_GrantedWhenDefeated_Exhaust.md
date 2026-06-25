# JTL_073 Grim Valor — Attached unit gains "When Defeated: you may exhaust a unit." The upgraded SOR_095
# (3/3 → 4/4, pre-damaged 1) dies attacking SOR_046; its granted When Defeated exhausts SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1
WithP1GroundArenaUpgrade: 0:JTL_073
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:EXHAUSTED
