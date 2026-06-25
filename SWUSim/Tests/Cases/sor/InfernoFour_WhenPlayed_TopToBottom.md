# SOR_031 Inferno Four — WhenPlayed scry 2: put top card on bottom, keep second.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1Hand: SOR_031
WithP1Resources: 2
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128|SOR_095

## EXPECT
P1DECKTOPCARD:SOR_128
