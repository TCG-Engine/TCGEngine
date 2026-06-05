# SOR_031 Inferno Four — WhenPlayed scry 2: keep both on top but swap order.

## GIVEN
P1LeaderBase: SOR_001/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1Hand: SOR_031
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128,SOR_095|

## EXPECT
P1DECKTOPCARD:SOR_128
