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
#// (Extra answer since 2026-08-14: this "you may discard" offer no longer auto-resolves a lone target.)

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
- P1>AnswerDecision:myHand-0

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


---

# Deploy_ThreeCardDeck_RemainingOneAutoToHand
#// JTL_014 Admiral Trench When Deployed with only 3 cards in deck: reveal 3, an opponent discards 2, one
#// card remains → the owner draws it (nothing left to discard). Net: deck 0, 2 discarded, hand +1.

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

## WHEN
- P1>DeployLeader
- P2>AnswerDecision:myTempZone-0&myTempZone-1
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1LEADER:DEPLOYED
P1DECKCOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:2

---

# Deploy_TwoCardDeck_NoDraw
#// JTL_014 Admiral Trench When Deployed with 2 cards in deck: reveal 2, an opponent discards both, nothing
#// remains → no draw. Deck 0, 2 discarded, hand +0.

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

## WHEN
- P1>DeployLeader
- P2>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P1LEADER:DEPLOYED
P1DECKCOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:2

---

# Deploy_OneCardDeck_DiscardCappedNoDraw
#// JTL_014 Admiral Trench When Deployed with 1 card in deck: reveal 1, the "discard 2" is capped to the 1
#// available, nothing remains → no draw. Deck 0, 1 discarded, hand +0.

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

## WHEN
- P1>DeployLeader
- P2>AnswerDecision:myTempZone-0

## EXPECT
P1LEADER:DEPLOYED
P1DECKCOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# LeaderAction_DeclineDiscard_EligibleInHand
#// JTL_014 Admiral Trench (leader) — Action [Exhaust]: Discard a card that costs 3 or more. This is a
#// "may": the player can decline even when eligible cost-3+ cards ARE in hand (distinct from the
#// LeaderAction_NoExpensiveCard_NoOp fizzle where nothing is eligible). Hand holds two cost-5 JTL_069;
#// the resulting choose-prompt is declined (PASS). Nothing is discarded, nothing is drawn, the deck is
#// untouched, no decision remains pending, and the leader still exhausts (the Action was spent).

## GIVEN
CommonSetup: gyk/bbk/{
  myLeader:JTL_014;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_069
WithP1Hand: JTL_069
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:PASS

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:0
P1DECKCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# Deploy_Blocked_WhenLeaderExhausted
#// JTL_014 Admiral Trench — the non-epic deploy Action requires the leader to be READY. With the leader
#// seeded exhausted (myLeader:JTL_014:0) and a comfortable 10 resources (well past both the 6+ control
#// threshold and the 3-resource cost), DeployLeader is a no-op: Trench stays in leader form, remains
#// exhausted, has not consumed his (non-existent) epic action, and the 10 resources are untouched.

## GIVEN
CommonSetup: gyk/bbk/{
  myLeader:JTL_014:0;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 10

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED
P1LEADER:EPICAVAILABLE
P1RESAVAILABLE:10

---

# Deploy_InsufficientReadyResources_NoOp
#// JTL_014 Admiral Trench — the deploy Action has TWO independent resource requirements: control 6+
#// resources AND pay 3 (which needs 3 READY resources). Here P1 controls 6 resources (passing the 6+
#// control threshold) but only 2 are READY (4 are exhausted), so the 3-resource cost cannot be paid and
#// DeployLeader is a no-op. This is distinct from Deploy_Requires6Resources_NoOp, which fails the 6+
#// control threshold itself. Trench stays in leader form; the 2 ready resources are untouched.

## GIVEN
CommonSetup: gyk/bbk/{
  myLeader:JTL_014;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 4:SOR_095:0,2:SOR_095:1

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1RESAVAILABLE:2

---

# Deploy_RevealDraw_LastPickOrderIndependent
#// JTL_014 Admiral Trench When Deployed — the final "draw 1 of the remaining, discard the other" pick is
#// order-independent: either remaining card may be chosen as the draw. Same setup as
#// Deploy_RevealOpponentDiscardsDraw (deck top 4 = SOR_095, SOR_237, SEC_080, SOR_225; P2 discards the
#// first two), but P1 now draws the OTHER remaining card (myTempZone-1 = SOR_225) and discards SEC_080.
#// Net is identical (deck 0, hand +1, discard 3) — only which card is drawn vs discarded swaps.

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
- P1>AnswerDecision:myTempZone-1

## EXPECT
P1LEADER:DEPLOYED
P1DECKCOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_225
P1DISCARDCOUNT:3
P1DISCARDUNIT:2:CARDID:SEC_080

---

# Deploy_DefeatedLeader_ReturnsExhausted
#// JTL_014 Admiral Trench — because his deploy is a NON-epic repeatable Action (it never consumes the
#// epic action), a defeated deployed Trench returns to leader form EXHAUSTED but with his deploy still
#// available, so he can redeploy on a later turn. Here a deployed Trench (myLeader:JTL_014:1:1) is
#// defeated by P2's Rival's Fall (SHD_079, on-aspect Vigilance for the bbk seat): he flips back to leader
#// form, is not deployed, is exhausted, and EPICAVAILABLE (redeployable). The actual redeploy requires a
#// fresh action phase (leader must re-ready), which the harness cannot advance to — see report.

## GIVEN
CommonSetup: gyk/bbk/{
  myLeader:JTL_014:1:1;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 6
WithP2Hand: SHD_079

## WHEN
- P2>PlayHand:0

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED
P1LEADER:EPICAVAILABLE

---

# Decline_SingleTarget_NoDiscardNoDraw
#// JTL_014 Admiral Trench (leader) — new since 2026-08-14: a "you may discard" offer with exactly ONE
#// legal target now prompts instead of auto-resolving, so the lone eligible card can be declined. Hand
#// holds a single cost-5 JTL_069; P1 declines. Nothing is discarded and nothing is drawn, but the cost
#// was still paid: the leader is exhausted and the Action is spent.

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
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1DECKCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION
