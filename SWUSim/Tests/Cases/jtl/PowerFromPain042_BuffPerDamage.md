# JTL_042 Power from Pain (event) — Give a unit +1/+0 this phase for each damage on it. SOR_046 (3/7)
# has 3 damage, so it gets +3/+0 → power 6.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_042
WithP1Resources: 3
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:7
