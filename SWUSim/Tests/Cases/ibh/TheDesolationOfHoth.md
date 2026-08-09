# DefeatsTwoCheapEnemies
#// IBH_104 The Desolation of Hoth (Event, cost 6, Vigilance) — Defeat up to 2 enemy units that each cost
#//   3 or less. Two cheap enemies (cost 2 and 1) are eligible; a cost-8 body is NOT a target and survives.

## GIVEN
CommonSetup: bbk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_104
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P1NODECISION

---

# NoCheapEnemy_Fizzles
#// IBH_104 The Desolation of Hoth — with only a cost-8 enemy (no unit costing 3 or less), there is no
#//   eligible target and the event fizzles cleanly.

## GIVEN
CommonSetup: bbk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_104
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P1NODECISION

---

# ChoosesOnlyOne_TheOtherEligibleUnitSurvives
#// IBH_104 The Desolation of Hoth — "defeat UP TO 2", so taking only ONE is legal. Two eligible enemies
#// are on board (SEC_080 cost 2, SOR_128 cost 1) plus an ineligible cost-8 LAW_124. P1 picks a single
#// target and stops: only that one is defeated, and BOTH the second eligible unit and the cost-8 body
#// survive. This is the "up to" lower branch — a mandatory-2 implementation would kill SOR_128 too.

## GIVEN
CommonSetup: bbk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_104
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:1:CARDID:LAW_124
P1NODECISION

---

# OnlyOneEligibleTarget_OfferExcludesTheExpensiveUnit
#// IBH_104 The Desolation of Hoth — with exactly one enemy costing 3 or less, the offer is EXACTLY that
#// unit: the cost-8 LAW_124 is not selectable. Asserting the offer (not just the outcome) is what proves
#// the "costs 3 or less" filter, since answering a target only ever proves the branch.
#// Note the "up to 2" multi-choose still PROMPTS with a single legal option — it does not auto-resolve.

## GIVEN
CommonSetup: bbk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_104
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0
P1DECISIONTOOLTIP:Defeat_up_to_2_enemy_units_that_cost_3_or_less

---

# TargetsAcrossBOTHArenas_GroundAndSpaceTogether
#// IBH_104 The Desolation of Hoth — "enemy units" is not arena-scoped, so one resolution may defeat a
#// GROUND unit and a SPACE unit together. Both SEC_080 (ground, cost 2) and SOR_225 (space, cost 1) are
#// offered and both are defeated, emptying both enemy arenas.

## GIVEN
CommonSetup: bbk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_104
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirSpaceArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:2
P1NODECISION
