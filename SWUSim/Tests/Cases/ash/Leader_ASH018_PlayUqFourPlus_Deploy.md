# ASH_018 Grogu — "When you play a unique unit that costs 4 or more: if this leader is ready, you may deploy
# him." P1 plays ASH_109 (unique, cost 4) and chooses to deploy Grogu (his only deploy path — no Epic Action).
## GIVEN
P1LeaderBase: ASH_018/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: ASH_109
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1LEADER:DEPLOYED
