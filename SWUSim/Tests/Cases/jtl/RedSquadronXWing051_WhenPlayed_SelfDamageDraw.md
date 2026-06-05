# JTL_051 Red Squadron X-Wing — When Played: You may deal 2 damage to this unit. If you do, draw a
# card. Taking the option damages the X-Wing (3/4 → 2 damage) and draws SOR_128 from the deck.

## GIVEN
P1LeaderBase: JTL_004/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_051
WithP1Resources: 3
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_051
P1SPACEARENAUNIT:0:DAMAGE:2
P1HANDCOUNT:1
P1DECKCOUNT:0
