# LAW_066 — "You MAY play 1." Declining the play means nothing is played and there is NO refill
# (the deck-resource clause is gated on "if you do"). P2 keeps its resource and deck intact, and P1's
# board stays empty.

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
WithP2Resources: 1:SOR_247:1
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P2RESCOUNT:1
P2DECKCOUNT:1
P1NODECISION
