# SOR_042 Search Your Feelings (event, cost 4) — "Search your deck for a card and draw it. (Then,
# shuffle your deck.)" P1 searches its 3-card deck and draws SOR_063; the deck drops to 2 (the rest
# shuffled back) and the event goes to discard.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_042
WithP1Resources: 4
WithP1Deck: SOR_063
WithP1Deck: SOR_095
WithP1Deck: SOR_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_063

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
P1DISCARDCOUNT:1
