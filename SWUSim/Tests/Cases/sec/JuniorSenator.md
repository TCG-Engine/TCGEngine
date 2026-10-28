# WhenPlayed_ReturnUpgrade
#// SEC_200 Junior Senator (Ground, 3/2, cost 2) — When Played: you may return an upgrade that costs 3 or
#//   less to its owner's hand. P1 returns SOR_120 (cost 2) from the enemy SOR_046 → P2's hand.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP1Hand: SEC_200

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# WhenPlayed_ReturnTokenUpgrade_DefeatsIt
#// SEC_200 Junior Senator — a token upgrade (Experience, cost 0) is a legal "cost 3 or less" target, but a
#//   token can't move to a non-play zone, so "returning" it DEFEATS it instead (CR). Targeting the enemy
#//   SOR_046 that holds only an Experience token removes the token and puts NOTHING in P2's hand.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP1Hand: SEC_200

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:0

---

# Offer_PicksTheUpgradeItself_EveryEligibleUpgradeOnEveryHost_PoolExact
#// COVERAGE (for the whole file; the two sections above predate it):
#//           offer=this section · decline=Decline_EligibleUpgradeInPlay_NothingReturned
#//           boundary=this section (SOR_054 cost 3 in / LAW_129 cost 4 out) + NoUpgradeCostingThreeOrLess_NoPrompt
#//           control=SameCardTwoOwners_TheChosenCopyGoesToITSOwner (same CardID, different owners on one host)
#//           reqboundary=AcrossTheRequestBoundary_ReturnsTheChosenUpgrade
#//           no-target=NoUpgradeCostingThreeOrLess_NoPrompt
#//           modes=2P,TwinSuns ("its owner's hand" names a seat; the pool spans every seat) · TeamSuns=N/A (no friendly/enemy wording)
#//
#// SEC_200 Junior Senator — "When Played: You may return an UPGRADE that costs 3 or less to its owner's
#//   hand." The player chooses the UPGRADE, not a unit: every eligible upgrade on every host is offered as a
#//   subcard mzID (<host>.u<sub>). P1's Battlefield Marine carries SOR_120 (2) and a Weakness token (0) —
#//   BOTH offered separately. P2's Consular Security Force carries LAW_129 Mastery (4, out), SOR_054 Jedi
#//   Lightsaber (3, in) and a CAPTIVE SEC_080 (a captured unit is not an upgrade — out). P2's base carries the
#//   Fortify upgrade HMW_095 (1, in). Junior Senator itself enters with no upgrades.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_200
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: [0:SOR_120 0:HMW_T02]
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: [0:LAW_129 0:SOR_054]
WithP2GroundArenaCaptive: 0:SEC_080
WithP2BaseUpgrade: HMW_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1SELECTABLEEXACT:myGroundArena-0.u0&myGroundArena-0.u1&theirGroundArena-0.u1&theirBase-0.u0

---

# TwoEligibleOnOneHost_ReturnsTheChosenOne_NotTheFirst
#// THE REPORTED GAP. P2's unit carries two eligible upgrades — SOR_120 Academy Training (u0) and SOR_069
#//   Resilient (u1). P1 picks Resilient. Only Resilient returns to P2's hand; Academy Training stays. (A
#//   host-first pick that returns "the first eligible upgrade" sends Academy Training back instead.)

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_200
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: [0:SOR_120 0:SOR_069]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P2HANDCOUNT:1
P2HANDCARD:0:SOR_069

---

# WeaknessBesideARealUpgrade_ReturnsOnlyTheWeakness
#// P1's own Battlefield Marine carries SOR_120 (u0) and a Weakness token (u1). P1 returns the WEAKNESS: the
#//   token leaves play and ceases to exist (no card reaches a hand), the line is logged, and Academy
#//   Training stays on the Marine. A first-eligible return would have bounced Academy Training instead.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_200
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: [0:SOR_120 0:HMW_T02]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0.u1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1HANDCOUNT:0
LOGCONTAINS:returned a Weakness token on P1's [[SOR_095|Battlefield Marine]] (it left play)

---

# SameCardTwoOwners_TheChosenCopyGoesToITSOwner
#// THE CONTROL/OWNER CELL. Two copies of SOR_120 Academy Training sit on P2's unit: u0 is P2's own, u1 is
#//   the one P1 just played onto it. P1 picks u1 — that copy goes to its OWNER's hand (P1's), and P2's copy
#//   stays. A return keyed on the CardID alone takes the first copy and sends P2's card to P2's hand.
#//   (SOR_120 is Command: +2 off-aspect under this Cunning setup, so it costs 4; Junior Senator costs 2.)

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_120 SEC_200]
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_120

---

# Decline_EligibleUpgradeInPlay_NothingReturned
#// "You may" — declining with an eligible upgrade in play returns nothing.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_200
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:0
P1NODECISION

---

# NoUpgradeCostingThreeOrLess_NoPrompt
#// The boundary partner of the offer section: the only upgrade in play is LAW_129 Mastery (cost 4), so
#//   nothing is eligible and the "you may" is never offered.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_200
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:LAW_129

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENACOUNT:1

---

# FortifyUpgradeOnABase_ReturnsToItsOwnersHand
#// A Fortify upgrade (HMW_205 Intelligence Agency, cost 1) is attached to a BASE, not a unit, and is still
#//   "an upgrade that costs 3 or less" — it returns to its owner's (P2's) hand.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_200
WithP2BaseUpgrade: HMW_205

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0.u0

## EXPECT
P2BASE:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# WillrowHood_EnemyCantReturnHisLoneUpgrade_ThroughTheUpgradePick
#// SEC_061 Willrow Hood's protection survives the upgrade-first prompt: P2's Willrow carries exactly one
#//   friendly upgrade (SOR_120). It is offered (the pick is legal), but P1's enemy return is refused and
#//   SOR_120 stays attached.

## GIVEN
CommonSetup: yyw/grw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_200
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P2HANDCOUNT:0

---

# AcrossTheRequestBoundary_ReturnsTheChosenUpgrade
#// THE REQUEST-BOUNDARY CELL: TwoEligibleOnOneHost with a boundary before the answer. The pool and the
#//   chosen subcard must survive a fresh process — nothing about the pick may live in a PHP global.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_200
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: [0:SOR_120 0:SOR_069]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0.u1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P2HANDCARD:0:SOR_069

---

# TwinSuns_ReturnsAFarSeatsUpgrade_ToThatSeatsHand
#// THE SEAT-COUNT CELL. At four seats the far seat's upgrade is addressed p3GroundArena-0.u0 — an mzID that
#//   does not exist at two seats — and the server refuses any answer outside the offered pool, so this
#//   section proves the pool reaches seat 3. "Its owner's hand" = seat 3's hand, not seat 2's or P1's.

## GIVEN
CommonSetup: yyw/yyk/{myResources:2}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1Hand: SEC_200
WithP3GroundArena: SOR_046:1:0
WithP3GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0.u0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:UPGRADECOUNT:0
P3HANDCOUNT:1
P2HANDCOUNT:0
P1HANDCOUNT:0
