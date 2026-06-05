# JTL_226 Radiant VII — When Played: Deal 5 indirect to a player. P1 deals 5 indirect to P2's base.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_226
WithP1Resources: 15

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:5
