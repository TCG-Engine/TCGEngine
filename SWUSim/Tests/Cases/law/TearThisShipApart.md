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

---

# StealEvent_AppliesSawGerreraTax
#// Behavior change (LAW_066 routed through ActivateCard): playing an opponent's EVENT from their resources
#// now applies play-time taxes like any event play. P2 controls Saw Gerrera SOR_153 → P1 (its opponent)
#// playing an event pays 2 to P1's base. P1 plays LAW_066 (its OWN event, from hand) → Saw Gerrera taxes 2;
#// then LAW_066 steals LAW_244 (Unmarked Credits) from P2's resources → playing THAT event now also taxes 2,
#// so P1's base takes 4 total, AND P1 gets a Credit token. (Before routing the stolen play through
#// ActivateCard the bypass path skipped Saw Gerrera on it → P1BASEDMG was only 2.)

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
WithP2GroundArena: SOR_153:1:0
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1BASEDMG:4
P1CREDITCOUNT:1
P2DISCARDCOUNT:1

---

# TwinSuns_LooksAtTheCHOSENSeatsResources
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. "Look at all of AN OPPONENT's resources. You may play 1 of
#// those cards for free. If you do, THAT OPPONENT resources the top card of their deck."
#// One seat is named and ALL THREE clauses hang off it — the look, the offer, and the refill.
#// ⚠ The pool was ZoneSearch("theirResources"), which fans out across every opponent above two seats, so
#//   the caster could look at seat 3's row and play SEAT 4's card. Now scoped to p{n}Resources.
#// ⚠ FILTER to opponents who HAVE a resource — an empty row has nothing to look at and nothing to play.
#// Seats 2 and 3 have resources; SEAT 4 HAS NONE and must NOT be offered.
#// Mutation check: drop $eligible and P1OPTIONNOT:P4 reds.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Resources: 13
WithP1Hand: LAW_066
WithP2Resources: 3
WithP3Resources: 3
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONNOT:P4
P1OPTIONNOT:P1
