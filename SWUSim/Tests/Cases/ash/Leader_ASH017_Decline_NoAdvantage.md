# ASH_017 Greef Karga — declining the optional exhaust gives no Advantage and leaves Greef ready. P1 plays
# SOR_095 and declines.
## GIVEN
P1LeaderBase: ASH_017/SOR_024
P2LeaderBase: SOR_010/SOR_020
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
