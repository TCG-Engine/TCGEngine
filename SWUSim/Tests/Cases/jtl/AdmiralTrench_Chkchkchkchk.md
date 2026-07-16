# Deploy_Requires6Resources_NoOp
#// JTL_014 Admiral Trench — the deploy action requires controlling 6 or more resources (separate from
#// the 3-resource cost). With only 5 resources P1 cannot deploy: DeployLeader is a no-op, Trench stays
#// in leader form, and the 5 resources are untouched.

## GIVEN
CommonSetup: gyk/bbk/{
  myLeader:JTL_014;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 5
WithP1Deck: SOR_095
WithP1Deck: SOR_237

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1RESAVAILABLE:5
P1DECKCOUNT:2

---

# Deploy_RevealOpponentDiscardsDraw
#// JTL_014 Admiral Trench — non-epic deploy (Action [3 resources, Exhaust], control 6+ resources) +
#// When Deployed: reveal top 4, an opponent discards 2, draw 1 of the remaining and discard the other.
#// Deck top 4 = SOR_095, SOR_237, SEC_080, SOR_225. P2 discards the first two (myTempZone-0&-1); P1 then
#// draws SEC_080 (myTempZone-0 of the remaining two) and discards SOR_225. Net: deck 0, discard 3
#// (SOR_095, SOR_237, SOR_225), hand +1, and Trench pays 3 of 6 resources. EPICAVAILABLE proves the
#// deploy did NOT consume the epic action (it is repeatable).

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

---

# LeaderAction_DiscardThreePlus_Draw
#// JTL_014 Admiral Trench (leader) — Action [Exhaust]: Discard a card that costs 3 or more from your
#// hand. If you do, draw a card. P1's only hand card JTL_069 (cost 5) is discarded and P1 draws SOR_128
#// from the deck.

## GIVEN
CommonSetup: gyk/bbk/{
  myLeader:JTL_014;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_069
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_069
P1HANDCOUNT:1
P1DECKCOUNT:0
P1LEADER:EXHAUSTED

---

# LeaderAction_NoExpensiveCard_NoOp
#// JTL_014 Admiral Trench (leader) — the discard requires a card costing 3 or more. With only a cost-1
#// card in hand (SOR_225), there is no eligible card to discard, so the action fizzles: nothing is
#// discarded, nothing is drawn, and no decision is pending. The leader still exhausts.

## GIVEN
CommonSetup: gyk/bbk/{
  myLeader:JTL_014;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_225
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1DECKCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION
