# Decline
#// LAW_066 — "You MAY play 1." Declining the play means nothing is played and there is NO refill
#// (the deck-resource clause is gated on "if you do"). P2 keeps its resource and deck intact, and P1's
#// board stays empty.

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

---

# StealEvent
#// LAW_066 — stealing an EVENT from an opponent's resources. P2's resource is LAW_244 (Create a Credit
#// token). P1 plays it for free → the effect resolves under P1 (P1 gets the Credit), and the event card
#// goes to its OWNER's (P2's) discard. P2 then refills from deck.

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

---

# StealUnit
#// LAW_066 Tear This Ship Apart — look at an opponent's resources, play one for free; that opponent
#// resources their deck-top. P2's only resource is SOR_247 (a unit). P1 plays it for free → it enters
#// P1's arena (owned by P2, controlled by P1). P2 then refills from deck (SOR_095), so P2's resource
#// count is unchanged and their deck drops by 1. The refill enters EXHAUSTED ("resources the top card",
#// not "as a ready resource"), so P2 has 0 ready resources afterward.

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
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_247
P2RESCOUNT:1
P2RESAVAILABLE:0
P2DECKCOUNT:0

---

# StealUpgrade
#// LAW_066 — stealing an UPGRADE from an opponent's resources. P2's resource is SOR_120 Academy Training
#// (+2/+2). P1 controls SOR_247 (2/3) as the only valid host, so the attach auto-resolves: SOR_247
#// becomes 4/5. P2 then refills from deck.

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
WithP1GroundArena: SOR_247:1:0
WithP2Resources: 1:SOR_120:1
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_247
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2RESCOUNT:1
P2RESAVAILABLE:0
P2DECKCOUNT:0

---

# NoOpponentResources
#// LAW_066 Tear This Ship Apart — with the opponent holding NO resources there is nothing to look at, so
#// the event resolves with no effect: nothing enters P1's board and P2's deck/resources are untouched.

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
WithP2Resources: 0
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENACOUNT:0
P2RESCOUNT:0
P2DECKCOUNT:1
P1NODECISION

---

# StealUpgradeWithFriendlyRestriction
#// LAW_066 Tear This Ship Apart — an upgrade with a "friendly unit" restriction (LOF_091 Craving Power)
#// stolen from P2's resources still attaches to a FRIENDLY unit (P1's SOR_046, the only host) and its
#// When Played fires under P1: it deals damage equal to the attached unit's power (3) to the enemy SOR_095
#// Battlefield Marine (3/3), defeating it. P2 then refills its resource from deck.

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
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Resources: 1:LOF_091:1
WithP2Deck: SOR_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENACOUNT:0
P2RESCOUNT:1
P2DECKCOUNT:0
