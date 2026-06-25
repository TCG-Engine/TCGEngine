# JTL_119 Resupply Carrier — When Played: You may put the top card of your deck into play as a resource.
# After playing JTL_119 for 6, P1 ramps the top deck card into resources (7 total resources, deck empty).

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_119
WithP1Resources: 6
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1RESCOUNT:7
P1DECKCOUNT:0
