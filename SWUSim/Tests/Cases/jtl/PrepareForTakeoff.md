# SearchTwoVehicles
#// JTL_128 Prepare for Takeoff — Search the top 8 cards for up to 2 Vehicle units, reveal and draw them.
#// Deck top: 2 Vehicles (SOR_225, SOR_044) + 1 Trooper; P1 draws both Vehicles, the Trooper goes to bottom.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_128
WithP1Resources: 2
WithP1Deck: SOR_225
WithP1Deck: SOR_044
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_225,SOR_044

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:1
