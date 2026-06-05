# JTL_051 Red Squadron X-Wing — declining the optional self-damage means no damage and no draw.

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
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_051
P1SPACEARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:0
P1DECKCOUNT:1
