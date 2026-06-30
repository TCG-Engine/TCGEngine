# SOR_125 Prepare for Takeoff — search top 8: choose 1 of 1 matching Vehicle unit.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
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
- P1>AnswerDecision:SOR_244

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:9
