# SOR_042 Search Your Feelings — the searcher may choose to draw nothing; the deck is reshuffled and
# stays at 3, no card enters hand.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_042
WithP1Resources: 4
WithP1Deck: SOR_063
WithP1Deck: SOR_095
WithP1Deck: SOR_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
P1DISCARDCOUNT:1
