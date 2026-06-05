# JTL_089 The Invisible Hand — When Played: search the top 8 cards of your deck for a Droid unit, reveal
# it, and draw it. The deck has one Droid (SEC_080); P1 draws it, the other two cards go to the bottom.
# SEC_080 costs 2, so the "If it costs 2 or less, you may play it for free" rider offers a YESNO — here
# P1 DECLINES (NO), so the drawn Droid stays in hand (hand 1, deck 2). See the YES branch in
# InvisibleHand089_WhenPlayed_SearchDroid_PlayFree.

## GIVEN
P1LeaderBase: JTL_005/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_089
WithP1Resources: 6
WithP1Deck: [SEC_080 SOR_095 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
P1NODECISION
