# JTL_089 The Invisible Hand — When Played: search top 8 for a Droid, reveal and draw it; "If it costs
# 2 or less, you may play it for free." The deck's lone Droid SEC_080 costs 2, so P1 draws it then
# accepts the free-play YESNO. P1 starts with exactly 6 resources, all exhausted paying for JTL_089
# (cost 6), so there are NO ready resources left — SEC_080 still enters play, proving it was free.
# Result: JTL_089 in space (1), SEC_080 in ground (1), hand empty, the other 2 cards on the deck bottom.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_089
WithP1Resources: 6
WithP1Deck: [SEC_080 SOR_095 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:0
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1DECKCOUNT:2
P1NODECISION
