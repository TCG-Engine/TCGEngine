# Offer_CostThreeInCostFourOut_TokensBasesAndItselfIncluded
#// COVERAGE: offer=this section · decline=Decline_NothingReturned
#//           boundary=this section (Booma Ball 3 in / LAW_129 Mastery 4 out)
#//           control=ReturnsToTheOwnersHand (the upgrade goes to P2's hand, not the caster's)
#//           reqboundary=AcrossTheRequestBoundary
#//           no-target=N/A (STRUCTURAL: the Booma Ball costs 3 and is itself attached while its When Played resolves)
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_236 Booma Ball — Upgrade +2/+2, cost 3, [Cunning], Item/Weapon.
#// "When Played: You may return an upgrade that costs 3 or less to its owner's hand."
#// HMW_222 Sandcrawler Sales Team's clause, ungated. P1 has no units, so Booma Ball's host choice is
#// P2's SOR_046 (u0 is Mastery, u1 the Shield, u2 the Booma Ball after it attaches) — pick it, then the
#// offer holds the Shield (cost 0), the Booma Ball (3), and P2's base Fortify HMW_095 (1). Mastery (4) is out.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_236
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: [0:LAW_129 0:SOR_T02]
WithP2BaseUpgrade: HMW_095

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1SELECTABLEEXACT:theirGroundArena-0.u1&theirGroundArena-0.u2&theirBase-0.u0

---

# ReturnsToTheOwnersHand
#// P1's Booma Ball on P1's SOR_095; P2's SOR_120 Academy Training (cost 2) on P2's SOR_046 is returned —
#// to P2's hand.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_236
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2HANDCOUNT:1
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:POWER:5

---

# CanReturnItself

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_236
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0.u0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1HANDCOUNT:1

---

# TokenUpgradeCeases_NotToHand

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_236
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2HANDCOUNT:0

---

# Decline_NothingReturned

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_236
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:0
P1NODECISION

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_236
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1
