# BounceAndOwnerReplays
#// SHD_207 A New Adventure (2-cost event, Cunning) — "Return a non-leader unit that costs 6 or less to its
#// owner's hand. Then, its owner may play it for free." P1 returns the enemy SEC_080 (cost 3); its owner P2
#// chooses to replay it for free, so it returns to P2's ground.
#// COVERAGE: offer=Offer_BothSidesNonLeaderUnitsCostingSixOrLess (pending P1SELECTABLEEXACT — friendly
#//           AND enemy units in both arenas, leaders out, a 7+-cost unit out) ·
#//           boundary=Offer_BothSidesNonLeaderUnitsCostingSixOrLess pairs a cost-6 unit (in) against a
#//           cost-8 unit (out) on the "6 or less" threshold, and NoValidTarget_TheEventIsStillPlayable
#//           AndDoesNothing is the empty-pool end of the same filter ·
#//           control=ForeignOwnedUnit_ReturnsToItsOwnerAndTheOwnerReplaysIt (a unit OWNED by P1 but
#//           CONTROLLED by P2 goes to P1's hand and P1 gets the free-play offer — owner, not controller)
#//           plus BounceAndOwnerReplays / OpponentDeclinesTheFreePlay, where the offer crosses to P2 ·
#//           decline=OpponentDeclinesTheFreePlay ("MAY play it for free" — the unit stays in hand) ·
#//           reqboundary=RequestBoundary_TheFreePlayOfferSurvivesTheHop (the target is picked by P1 in
#//           one request and the free play is answered by the OWNER in another, so the pending offer and
#//           the returned card's hand index have to be read back out of the serialized gamestate).

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_207
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080

---

# Offer_BothSidesNonLeaderUnitsCostingSixOrLess
#// SHD_207 THE OFFER AXIS. "Return a NON-LEADER unit that costs 6 OR LESS" has no friendly/enemy
#// qualifier, so the pool spans both players and both arenas, minus leaders and minus anything costing 7+.
#// On the board: P1's SOR_095 (c2, in) and P1's SOR_237 (c2, in), P2's SOR_232 AT-ST (c6 — the inclusive
#// edge of "6 or less", in) and P2's SOR_052 Redemption (c8, out). Both leaders are DEPLOYED as units and
#// both are out. Cost here is the PRINTED cost, and a deployed leader seats at the END of its ground
#// arena, so P1's leader is myGroundArena-1 and P2's is theirGroundArena-1.
#// The decision is left PENDING so the offer itself is the assertion.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SHD_207
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_232:1:0
WithP2SpaceArena: SOR_052:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0

---

# FriendlyUnit_ReturnedAndReplayedForFreeByItsController
#// SHD_207 — the friendly half of "a non-leader unit": P1 bounces its OWN SHD_080 and, as its owner,
#// takes the free replay. Two things make this more than a round trip. The replay is a real PLAY, so
#// Crumb's When Played fires again and heals another point off P1's base (2 damage → 1). And it is FREE:
#// P1 starts with 5 resources, the event costs 2 and Crumb costs nothing, so 3 stay ready — Crumb's own
#// 1-cost never comes out. The enemy SEC_080 is a second legal target so the pick stays interactive.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5;myBaseDamage:2}
P1OnlyActions: true
WithP1Hand: SHD_207
WithP1GroundArena: SHD_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_080
P1BASEDMG:1
P1HANDCOUNT:0
P1RESAVAILABLE:3
P2GROUNDARENACOUNT:1

---

# OpponentDeclinesTheFreePlay
#// SHD_207 THE DECLINE BRANCH, and the reason the second clause is a separate decision at all: the owner
#// "MAY play it for free". P2 answers NO, so SEC_080 stays in P2's hand, P2's arena stays empty and no
#// resource of P2's is touched. The pair with BounceAndOwnerReplays (YES instead of NO) is what proves
#// the replay is optional rather than an automatic part of the return. P2 is given 2 resources purely so
#// "no resource of P2's is touched" is an assertable number rather than a vacuous zero.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;theirResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_207
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2HANDCARD:0:SEC_080
P2RESAVAILABLE:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_207

---

# ForeignOwnedUnit_ReturnsToItsOwnerAndTheOwnerReplaysIt
#// SHD_207 THE CONTROL AXIS. "its OWNER's hand … its OWNER may play it" — owner, not controller. SHD_080
#// is OWNED by P1 but CONTROLLED by P2 (the end state after a take-control effect). P1 bounces it: the
#// card goes to P1's hand, not P2's, and the free-play offer is P1's to take — so Crumb comes back under
#// P1 and its When Played heals P1's base (2 → 1). An implementation that used the CONTROLLER would send
#// the card to P2's hand and hand P2 the offer, which every assertion here would catch.
#// Controlled units seat AFTER the regular arena lines, so P2's ground is SEC_080 then SHD_080.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5;myBaseDamage:2}
WithActivePlayer: 1
WithP1Hand: SHD_207
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaControlled: SHD_080:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_080
P1BASEDMG:1
P1HANDCOUNT:0
P1RESAVAILABLE:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2HANDCOUNT:0

---

# NoValidTarget_TheEventIsStillPlayableAndDoesNothing
#// SHD_207 — the empty-pool end of the "6 or less non-leader unit" filter. The only body on the board is
#// P2's DEPLOYED LEADER, which the filter excludes, so there is nothing to return. The event is still a
#// legal play: it resolves with no decision at all, its own cost is still spent (2 of 4 resources), and
#// it goes to the discard. Nothing enters any arena and no hand changes.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SHD_207

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2NODECISION
P1HANDCOUNT:0
P2HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_207
P1RESAVAILABLE:2
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1

---

# RequestBoundary_TheFreePlayOfferSurvivesTheHop
#// SHD_207 spans two requests in production: P1 picks the unit to return in one, and the OWNER answers
#// the free-play offer in another. Nothing about that pending offer can live in request-local memory —
#// the returned card's identity and its index in the owner's hand both have to come back out of the
#// serialized gamestate. The boundary is inserted between P1's pick and P2's answer; the replay must
#// still happen exactly as in BounceAndOwnerReplays. Two enemy units keep P1's pick interactive.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_207
WithP2GroundArena: [SEC_080:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:2
P2HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_207
