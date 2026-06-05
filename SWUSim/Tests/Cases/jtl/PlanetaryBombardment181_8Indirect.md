# JTL_181 Planetary Bombardment — Deal 8 indirect to a player (12 if you control a Capital Ship). Without
# one, P1 deals 8 indirect to P2's base.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_181
WithP1Resources: 12

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:8
