# JTL_014 Admiral Trench — non-epic deploy (Action [3 resources, Exhaust], control 6+ resources) +
# When Deployed: reveal top 4, an opponent discards 2, draw 1 of the remaining and discard the other.
# Deck top 4 = SOR_095, SOR_237, SEC_080, SOR_225. P2 discards the first two (myTempZone-0&-1); P1 then
# draws SEC_080 (myTempZone-0 of the remaining two) and discards SOR_225. Net: deck 0, discard 3
# (SOR_095, SOR_237, SOR_225), hand +1, and Trench pays 3 of 6 resources. EPICAVAILABLE proves the
# deploy did NOT consume the epic action (it is repeatable).

## GIVEN
CommonSetup: gyk/bbk/{
  myLeader:JTL_014;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP1Deck: SEC_080
WithP1Deck: SOR_225

## WHEN
- P1>DeployLeader
- P2>AnswerDecision:myTempZone-0&myTempZone-1
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1LEADER:DEPLOYED
P1LEADER:EPICAVAILABLE
P1RESAVAILABLE:3
P1DECKCOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:3
P1DISCARDUNIT:2:CARDID:SOR_225
