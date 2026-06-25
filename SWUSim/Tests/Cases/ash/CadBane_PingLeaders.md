## GIVEN
CommonSetup: grk/brk/{
  theirLeader:SOR_010:1:1:1;
  myLeader:ASH_011:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED
