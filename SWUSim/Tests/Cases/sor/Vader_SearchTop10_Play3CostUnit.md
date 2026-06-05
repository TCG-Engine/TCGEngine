# SOR_087 Darth Vader — WhenPlayed search top 10: play one 3-cost Villainy unit for free.

## GIVEN
P1LeaderBase: SOR_007/SOR_024
P2LeaderBase: SOR_002/SOR_020
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
