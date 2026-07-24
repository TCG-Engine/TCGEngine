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
