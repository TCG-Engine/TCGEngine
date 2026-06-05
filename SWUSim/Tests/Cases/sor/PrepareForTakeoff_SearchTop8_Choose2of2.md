# SOR_125 Prepare for Takeoff — search top 8: choose 2 of 2 matching Vehicle units.

## GIVEN
P1LeaderBase: SOR_007/SOR_024
P2LeaderBase: SOR_002/SOR_020
SkipPreGame: true
WithP1Hand: SOR_125
WithP1Resources: 2
WithP1Deck: SOR_244
WithP1Deck: SOR_162
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
- P1>AnswerDecision:SOR_244,SOR_162

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:8
