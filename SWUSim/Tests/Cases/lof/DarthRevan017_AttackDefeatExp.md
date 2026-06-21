# LOF_017 Darth Revan — When a friendly unit attacks and defeats a unit: you may exhaust this leader to
# give that unit an Experience token. Plo Koon defeats SOR_059; P1 exhausts Revan to make Plo Koon 7/9.

## GIVEN
P1LeaderBase: LOF_017/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:7
P1LEADER:EXHAUSTED
