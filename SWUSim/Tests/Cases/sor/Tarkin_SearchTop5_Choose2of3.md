# SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose 2 of 3 matching Imperial cards.

## GIVEN
P1LeaderBase: SOR_007/SOR_024
P2LeaderBase: SOR_002/SOR_020
SkipPreGame: true
WithP1Hand: SOR_084
WithP1Resources: 4
WithP1Deck: SOR_085
WithP1Deck: SOR_128
WithP1Deck: SOR_086
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085,SOR_128

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:6
