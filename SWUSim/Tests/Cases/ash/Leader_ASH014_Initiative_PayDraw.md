# ASH_014 The Mandalorian — "When you take the initiative: you may pay 1 resource; if you do, draw a card."
# P1 claims initiative and accepts, paying 1 resource (1 → 0) to draw SOR_095.
## GIVEN
P1LeaderBase: ASH_014/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 1
WithP1Deck: SOR_095
## WHEN
- P1>Claim
- P1>AnswerDecision:YES
## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:0
