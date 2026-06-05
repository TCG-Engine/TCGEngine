# SOR_203 Cunning (event, cost 4) — Give a unit +4/+0 this phase + Exhaust up to 2 units. SEC_080 (3/3)
# gets +4/+0 (POWER 7) then is exhausted. Cunning is off-aspect for SOR_009 → cost 6.

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_203
WithP1Resources: 8
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:BuffUnit
- P1>AnswerDecision:Exhaust
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:EXHAUSTED
P1DISCARDCOUNT:1
