# BounceCheapUnit
#// LAW_246 The Axe Forgets (Cunning event, cost 2) — "Return a non-leader unit that costs 3 or less to
#// its owner's hand." SEC_080 (cost 2) is the only unit -> auto-target -> returned to P2's hand.
#// COVERAGE: offer=OfferIsCost3OrLessNonLeaderBothSides (pending SELECTABLEEXACT; cost-3-in/cost-4-out
#//           boundary pair + deployed-leader exclusion) · decline=N/A (mandatory single effect, no "you
#//           may") · control=StolenUnitReturnsToOwnersHand (P1-controlled, P2-owned unit returns to P2's
#//           hand) · reqboundary=N/A (single-decision event, no post-decision state read) ·
#//           no-target branch=NoValidTargets_ResolvesWithNoEffect

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_246

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1DISCARDCOUNT:1

---

# OfferIsCost3OrLessNonLeaderBothSides
#// LAW_246 The Axe Forgets — the offer spans BOTH players and BOTH arenas but only non-leader units of
#//   printed cost 3 or less: cost-2 SOR_095, cost-3 SOR_248 and cost-2 space SOR_237 are in; cost-4
#//   SOR_046, cost-6 SOR_232 and P2's DEPLOYED LEADER unit are out. Decision left PENDING to assert the
#//   offer itself (cost-3-in / cost-4-out is the boundary pair).

## GIVEN
CommonSetup: yyk/bgw/{myResources:2;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: LAW_246
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:0 SOR_248:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_232:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2&mySpaceArena-0&theirGroundArena-0
P1HASDECISION

---

# ReturnFriendlyUnit
#// LAW_246 The Axe Forgets — a FRIENDLY unit is a legal pick; it returns to its owner's (P1's) hand.
#//   Two candidates (one per side) so the pick is explicit, then P1's SOR_095 leaves the arena for hand.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
P1OnlyActions: true
WithP1Hand: LAW_246
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P2GROUNDARENACOUNT:1
P1DISCARDCOUNT:1
P1NODECISION

---

# NoValidTargets_ResolvesWithNoEffect
#// LAW_246 The Axe Forgets — with no unit of cost 3 or less in play (only cost-4 SOR_046s), the event
#//   still resolves: cost is paid, the card lands in discard, and both boards are untouched.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
P1OnlyActions: true
WithP1Hand: LAW_246
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2HANDCOUNT:0
P1HANDCOUNT:0
P1NODECISION

---

# StolenUnitReturnsToOwnersHand
#// LAW_246 The Axe Forgets — "to its OWNER's hand": bouncing a P2-owned unit that P1 currently CONTROLS
#//   must put it in P2's hand, not P1's. P1 controls a P2-owned SEC_080 (lone candidate, auto-target).

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
P1OnlyActions: true
WithP1Hand: LAW_246
WithP1GroundArenaControlled: SEC_080:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:1
P2HANDCARD:0:SEC_080
P1DISCARDCOUNT:1
