# SOR_084 Grand Moff Tarkin — WhenPlayed search top 5: choose 1 of 3 matching Imperial cards.

## GIVEN
CommonSetup: ggk/bbk
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
- P1>AnswerDecision:SOR_085

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:7
