# JTL_240 Fett's Firespray — When Played: 1 indirect to a player (2 if you control Boba Fett). Without
# Boba Fett, P1 deals 1 indirect to P2's base.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_240
WithP1Resources: 12

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:1
