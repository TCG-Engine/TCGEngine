# Offer_BaseUpgradesOnBothSides_UnitUpgradesExcluded
#// COVERAGE: offer=this section · decline=Decline_NothingDefeated
#//           boundary=N/A (STRUCTURAL: no number — the qualifier is WHERE the upgrade is)
#//           control=N/A (a defeated upgrade goes to its OWNER's discard via the shared SWUDefeatUpgrade)
#//           reqboundary=AcrossTheRequestBoundary · no-target=OnlyUnitUpgrades_NoPrompt
#//           modes=2P only (no player reference; "a base" has no friendly/enemy wording)
#//
#// HMW_270 Wild Space Wanderer — Unit (Space) 3/2, cost 3, no aspects, Fringe/Vehicle/Fighter.
#// "When Played: You may defeat an upgrade on a base."
#// P1 base: HMW_095 Carbonite Chamber · P2 base: HMW_095 · P2 SOR_046 carries SOR_120 Academy Training
#// (an upgrade, but on a unit — out).

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_270
WithP1BaseUpgrade: HMW_095
WithP2BaseUpgrade: HMW_095
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myBase-0.u0&theirBase-0.u0

---

# DefeatsAnEnemyBaseUpgrade

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_270
WithP1BaseUpgrade: HMW_095
WithP2BaseUpgrade: HMW_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0.u0

## EXPECT
P2BASE:UPGRADECOUNT:0
P1BASE:UPGRADECOUNT:1
P2DISCARDCOUNT:1

---

# DefeatsYourOwnBaseUpgrade

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_270
WithP1BaseUpgrade: HMW_095
WithP2BaseUpgrade: HMW_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0.u0

## EXPECT
P1BASE:UPGRADECOUNT:0
P2BASE:UPGRADECOUNT:1
P1DISCARDCOUNT:1

---

# OnlyUnitUpgrades_NoPrompt

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_270
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: [0:SOR_120 0:SOR_T02]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# Decline_NothingDefeated

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_270
WithP2BaseUpgrade: HMW_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASE:UPGRADECOUNT:1
P2DISCARDCOUNT:0
P1NODECISION

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_270
WithP1BaseUpgrade: HMW_095
WithP2BaseUpgrade: HMW_095

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0.u0

## EXPECT
P2BASE:UPGRADECOUNT:0
P1BASE:UPGRADECOUNT:1
