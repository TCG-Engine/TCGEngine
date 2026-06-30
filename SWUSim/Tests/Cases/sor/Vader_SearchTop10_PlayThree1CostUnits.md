# SOR_087 Darth Vader — WhenPlayed search top 10: play three 1-cost Villainy units for free.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_087
WithP1Resources: 7
WithP1Deck: SOR_225
WithP1Deck: SOR_225
WithP1Deck: SOR_225
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_225,SOR_225,SOR_225
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:3
P1DECKCOUNT:9
