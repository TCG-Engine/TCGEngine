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
