# SOR_155 Aggression (event, cost 4) — Draw a card + Deal 4 to a unit. P1 draws (hand 0→1) and deals 4
# to the only unit (LAW_124, a 4/7, survives at 4). Aggression is off-aspect for SOR_009 → cost 6.

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_155
WithP1Resources: 8
WithP1Deck: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Draw
- P1>AnswerDecision:Deal4

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4
P1DISCARDCOUNT:1
