# JTL_089 The Invisible Hand — When Played: search the top 8 cards of your deck for a Droid unit, reveal
# it, and draw it. If it costs 2 or less, you may play it for free.

## GIVEN
P1LeaderBase: JTL_005/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_089
WithP1Resources: 6
WithP1Deck: [LOF_158 SOR_095 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LOF_158

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
P1NODECISION
