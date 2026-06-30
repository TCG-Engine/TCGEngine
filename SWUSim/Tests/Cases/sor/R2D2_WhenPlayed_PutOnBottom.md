# SOR_236 R2-D2 — WhenPlayed scry 1: put top card on bottom.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1Hand: SOR_236
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128
