# LAW_237 Qui-Gon Jinn (3/5, Sentinel) — When Played/On Attack: look at the top 3, you may discard 1,
# put the rest back on top. Attacks the base; discard the top SOR_237.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_237:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDeck-0

## EXPECT
P1DECKCOUNT:2
P1DISCARDCOUNT:1
