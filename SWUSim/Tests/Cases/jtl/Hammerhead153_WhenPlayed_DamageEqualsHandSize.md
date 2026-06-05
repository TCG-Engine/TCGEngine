# JTL_153 Rebellious Hammerhead — When Played: You may deal damage to a unit equal to the number of
# cards in your hand. After playing JTL_153 (from a 3-card hand), 2 cards remain, so it deals 2 to
# SOR_046. Counting is at resolution (the just-played card is no longer in hand).

## GIVEN
P1LeaderBase: JTL_012/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_153
WithP1Hand: SOR_225
WithP1Hand: SOR_237
WithP1Resources: 6
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1HANDCOUNT:2
