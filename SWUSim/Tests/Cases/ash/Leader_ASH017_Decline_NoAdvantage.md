# ASH_017 Greef Karga — declining the optional exhaust gives no Advantage and leaves Greef ready. P1 plays
# SOR_095 and declines.
## GIVEN
CommonSetup: gyw/brk/{
  myLeader:ASH_017
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1LEADER:READY
