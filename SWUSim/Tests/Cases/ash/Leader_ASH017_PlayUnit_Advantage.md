# ASH_017 Greef Karga — "When you play or create a unit: you may exhaust this leader; if you do, give an
# Advantage token to that unit." P1 plays SOR_095 and exhausts Greef to give it an Advantage token.
## GIVEN
P1LeaderBase: ASH_017/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1LEADER:EXHAUSTED
