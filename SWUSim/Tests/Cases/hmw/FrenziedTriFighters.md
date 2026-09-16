# Offer_CostThreeInCostFourOut_TokensAndBasesIncluded
#// COVERAGE: offer=this section · decline=Decline_NothingDefeated
#//           boundary=this section (SOR_120 2 in, HMW_236 Booma Ball 3 in / LAW_129 Mastery 4 out)
#//           control=N/A (a defeated upgrade goes to its OWNER's discard via the shared SWUDefeatUpgrade)
#//           reqboundary=AcrossTheRequestBoundary · no-target=NoUpgradeInPlay_NoPrompt
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_249 Frenzied Tri-Fighters — Unit (Space) 5/3, cost 5, [Villainy], Separatist/Droid/Vehicle/Fighter.
#// "When Played: You may defeat an upgrade that costs 3 or less."
#// P2 SOR_046: u0 Academy Training (2) · u1 Mastery (4) · u2 Shield token (0) · u3 Booma Ball (3).
#// P1 base: HMW_095 Carbonite Chamber (1).

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_249
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: [0:SOR_120 0:LAW_129 0:SOR_T02 0:HMW_236]
WithP1BaseUpgrade: HMW_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0.u0&theirGroundArena-0.u2&theirGroundArena-0.u3&myBase-0.u0

---

# DefeatsAnEnemyUpgrade_ToItsOwnersDiscard

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_249
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: [0:SOR_120 0:LAW_129]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:6
P2DISCARDCOUNT:1
P1DISCARDCOUNT:0

---

# DefeatsAnUpgradeOnABase
#// P2's base Fortify HMW_095 — base upgrades are upgrades in play too.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_249
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2BaseUpgrade: HMW_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0.u0

## EXPECT
P2BASE:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:1

---

# NoUpgradeInPlay_NoPrompt
#// Only a Mastery (4) in play — nothing legal, so nothing is offered.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_249
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:LAW_129

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# Decline_NothingDefeated

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_249
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: [0:SOR_120 0:SOR_T02]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2DISCARDCOUNT:0
P1NODECISION

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_249
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: [0:SOR_120 0:SOR_T02]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0.u1

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:0
