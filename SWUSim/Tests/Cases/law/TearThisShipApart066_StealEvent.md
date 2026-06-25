# LAW_066 — stealing an EVENT from an opponent's resources. P2's resource is LAW_244 (Create a Credit
# token). P1 plays it for free → the effect resolves under P1 (P1 gets the Credit), and the event card
# goes to its OWNER's (P2's) discard. P2 then refills from deck.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 13
WithP1Hand: LAW_066
WithP2Resources: 1:LAW_244:1
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1CREDITCOUNT:1
P2RESCOUNT:1
P2RESAVAILABLE:0
P2DECKCOUNT:0
P2DISCARDCOUNT:1
