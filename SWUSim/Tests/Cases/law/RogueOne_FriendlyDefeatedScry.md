# LAW_119 Rogue One (3/3, space) — When a friendly unit is defeated: look at the top 2 cards; put any
# number on the bottom, rest on top. SOR_128 attacks SOR_046 and dies; put the top SOR_237 on the
# bottom -> new top is SOR_095.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_119:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myDeck-0

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:2
