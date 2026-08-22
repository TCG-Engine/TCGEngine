# BounceEnemyUnit
#// SEC_233 Beguile (event, cost 3) — Look at an opponent's hand; choose a non-leader unit that opponent
#//   controls that costs 6 or less and return it to its owner's hand. P1 bounces SOR_046 (cost 4) → P2 hand.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_233

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# CostOver6_NotSelectable
#// SEC_233 Beguile — only NON-LEADER enemy units costing 6 or LESS may be returned. Against SOR_232
#//   AT-ST (cost 6), SOR_046 Consular Security Force (cost 4) and SOR_040 Avenger (cost 9, space), only
#//   the two ≤6 ground units are legal targets; the cost-9 Avenger is excluded.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_040:1:0
WithP1Hand: SEC_233

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# BounceCost6_Boundary_AvengerStays
#// SEC_233 Beguile — cost 6 is inclusive: the SOR_232 AT-ST (cost 6) can be returned to the opponent's
#//   hand, while the cost-9 SOR_040 Avenger stays in the space arena.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_040:1:0
WithP1Hand: SEC_233

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2SPACEARENACOUNT:1
P2HANDCOUNT:1

---

# LooksAtTheOpponentsHand_BeforeChoosing
#// SEC_233 Beguile — the FIRST clause, "Look at an opponent's hand", which was unimplemented until
#// 2026-08-18 and reported as "Beguile not showing cards in hand".
#//
#// Why it was invisible to a green suite: every other section in this file leaves P2's hand EMPTY, and
#// the reveal popup no-ops on an empty hand. So the clause could be entirely absent and nothing failed.
#// The hand is seeded here on purpose.
#//
#// Why a popup at all: the client reveals an opponent's Visibility=Self hand only while the viewer has a
#// pending decision whose param names `theirHand`. Beguile's only choice is over units in the ARENA, so
#// there is no such decision — the hand has to be presented explicitly.
#// The look must come BEFORE the bounce choice: you are choosing what to tempo out, and the hand is the
#// information you are meant to make that choice on. Asserted by leaving the popup pending and checking
#// the unit choice has not been raised yet.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2Hand: [SOR_095 SOR_128]
WithP1Hand: SEC_233

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Opponent's_hand
P2HANDCOUNT:2
P2GROUNDARENACOUNT:1

---

# LookIsInformationOnly_HandIsUntouched
#// SEC_233 Beguile — "look at" takes nothing. After acknowledging the popup and bouncing the unit, P2's
#// hand holds its original 2 cards PLUS the returned unit. A reveal that discarded, drew or reordered
#// would show up here as a different count.
#// This is also the section that proves the popup does not swallow the bounce: the whole ability still
#// resolves after the acknowledge.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2Hand: [SOR_095 SOR_128]
WithP1Hand: SEC_233

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:OK

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:3
P1NODECISION

---

# EmptyOpponentHand_NoPopup_StillBounces
#// SEC_233 Beguile — the boundary partner: with nothing to look at, the reveal must not raise a dead
#// popup the player has to dismiss, and the bounce clause still resolves on its own. ("Look at an
#// opponent's hand" with an empty hand is legal and simply shows nothing.)
#// Together with the section above this pins the no-op: popup on a non-empty hand, none on an empty one.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_233

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1NODECISION

---

# TwinSuns_LooksAtTheCHOSENSeatAndBouncesFromTHATSeatOnly
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. Beguile names ONE opponent and BOTH clauses hang off it:
#// "Look at AN OPPONENT's hand. Then, choose a non-leader unit THAT OPPONENT controls…". Two defects
#// above two seats, pointing in OPPOSITE directions:
#//   (a) TOO NARROW — the look used OtherPlayer(), so the caster saw a hand they never chose.
#//   (b) TOO WIDE — the bounce pool was 'side' => 'their', which fans out across EVERY opponent in Twin
#//       Suns. So the caster could look at seat 3's hand and then bounce SEAT 4's unit, which the card
#//       does not allow. ⚠ The sweep's inverse defect: the pool GREW, so nothing looked broken — no prompt
#//       went missing and nothing fizzled; it only shows as a target that should not be selectable.
#// Fixed with a picker plus 'ofSeat' on the offer, so the two clauses cannot disagree about who was named.
#//
#// ⚠ NO $eligible filter: the LOOK always resolves — against an empty hand, and against an opponent with
#//   nothing bounceable. Choosing a seat purely for the information is a legal line.
#//
#// P1 picks SEAT 3. Seat 3's hand is shown (the popup is left PENDING, which is how the look is asserted
#// — answering a prompt does not prove it was raised), and the bounce pool must contain ONLY seat 3's
#// eligible unit. Seat 2 and seat 4 each control a bounceable unit that must NOT be offered.
#// ⚠ A 2-player version CANNOT FAIL — with one opponent "their" IS the chosen seat.
#// Mutation check: drop 'ofSeat' and seats 2/4 appear in the pool; revert the look to OtherPlayer() and
#// the wrong hand is shown.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: SEC_233
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0
WithP2Hand: [SOR_095 SOR_128]
WithP3Hand: [SOR_095 SOR_128]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P1DECISIONTOOLTIP:Opponent's_hand
P2HANDCOUNT:2
P3HANDCOUNT:2
P2GROUNDARENACOUNT:1
P3GROUNDARENACOUNT:1
P4GROUNDARENACOUNT:1

---

# TwinSuns_BouncePoolIsONLYTheChosenSeatsUnits
#// ⚠ THE OVER-WIDE-POOL CELL — the second half of Beguile's Twin Suns fix, and it needs its own section
#// because the look-clause section above leaves the popup pending and never reaches the unit choice.
#// "…choose a non-leader unit THAT OPPONENT controls" is scoped to the ONE opponent already named by the
#// first clause. The pool was 'side' => 'their', which in Twin Suns fans out across every opponent — so
#// the caster could look at seat 3's hand and bounce seat 4's unit.
#// ⚠ This is the inverse of the usual defect: the pool GREW, so every existing test stayed green, no
#//   prompt went missing and nothing fizzled. It surfaces ONLY as an illegal target being selectable,
#//   which is why the assertion has to be SELECTABLEEXACT rather than an outcome.
#// P1 picks SEAT 3 and acknowledges the hand. The pool must then be EXACTLY seat 3's Battlefield Marine —
#// seats 2 and 4 each control an identical, equally bounceable unit that must not appear.
#// ⚠ SOR_046 costs 2 (≤ 6), so the cost filter admits every unit here; only the SEAT scoping excludes any.
#// ⚠ SEAT 3 deliberately holds TWO units: with a single eligible target SWUOfferUnitTarget auto-resolves
#//   and the bounce simply happens, leaving no decision to inspect (the first attempt at this section
#//   failed for exactly that reason). A pool assertion needs a pool with a real choice in it.
#// Mutation check: drop 'ofSeat' and p2/p4 appear in the pool.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: SEC_233
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: [SOR_046:1:0 SOR_095:1:0]
WithP4GroundArena: SOR_046:1:0
WithP3Hand: [SOR_095 SOR_128]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3
- P1>AnswerDecision:OK

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1SELECTABLEEXACT:p3GroundArena-0&p3GroundArena-1
