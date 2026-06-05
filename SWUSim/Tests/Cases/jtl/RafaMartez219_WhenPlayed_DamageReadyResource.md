# JTL_219 Rafa Martez — When Played: Deal 1 damage to a friendly unit and ready a resource. P1 deals 1
# to SOR_046 and readies a resource (6 resources, 5 paid off-aspect, 1 readied → 2 available).

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_219
WithP1Resources: 6
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1RESAVAILABLE:2
