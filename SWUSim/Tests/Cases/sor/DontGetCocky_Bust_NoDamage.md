# SOR_223 Don't Get Cocky — if the combined cost exceeds 7 you "bust" and deal NOTHING. P1 reveals
# SOR_043 (cost 8) and stops: 8 > 7, so the chosen unit takes 0. The revealed card returns to the deck.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_223
WithP1Resources: 6
WithP1Deck: SOR_043
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DECKCOUNT:3
P1DISCARDCOUNT:1
