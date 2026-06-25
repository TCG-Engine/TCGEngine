# SOR_087 Darth Vader — WhenPlayed search top 10: play one 3-cost Villainy unit for free.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_087
WithP1Resources: 7
WithP1Deck: SOR_229
WithP1Deck: SOR_063
WithP1Deck: SOR_063
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
- P1>AnswerDecision:SOR_229
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:0
P1DECKCOUNT:11
