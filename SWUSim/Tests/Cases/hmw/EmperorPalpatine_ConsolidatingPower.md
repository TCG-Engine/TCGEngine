# WhenPlayed_TakesControlOfCheapEnemy_AndGivesTwoWeakness
#// HMW_110 Emperor Palpatine, Consolidating Power — cost 5, [Command][Villainy], Ground 3/2, unique,
#// Traits: Force, Imperial, Sith, Official.
#// Text: "When Played: You may take control of an enemy non-leader unit that costs 3 or less.
#//        If you do, give 2 Weakness tokens to it."
#//
#// Three independent target restrictions — ENEMY, NON-LEADER, and printed cost <= 3 — each with its own
#// guard below. The control change is PERMANENT (no duration wording), so it must not use the
#// TEMPORARY_STEAL marker that RegroupPhaseStart auto-reverts.
#//
#// COVERAGE: offer=WhenPlayed_Offer_ExcludesFriendlyAndTooExpensive (SELECTABLEEXACT)
#//                 + WhenPlayed_DarksaberLeaderUnit_NotOffered (the live-object leader gate)
#//           decline=WhenPlayed_Decline_NoControlChange_NoTokens
#//           boundary=WhenPlayed_TakesControlOfCheapEnemy_AndGivesTwoWeakness (cost exactly 3, taken)
#//                 vs WhenPlayed_OnlyCostFourEnemy_NoPrompt (cost 4, not even offered)
#//           control=the whole card + WhenDefeatedAfterSteal_GoesToOwnersDiscard (owner != controller)
#//                 + ControlChangeIsPermanent_SurvivesRegroup
#//           reqboundary=N/A (no state written before the decision and read behind it — the target
#//                 rides the answer, and the 2 tokens are applied in the same continuation)
#//
#// ⚠ The "If you do" FAILURE branch is currently UNEXERCISABLE and therefore untested: the only unit
#// that can refuse a take-control is LAW_149 Rey ("Opponents can't take control of this unit"), and she
#// costs 8 — outside this card's <=3 pool. The gate IS implemented (the continuation acts on
#// SWUTakeControlOfUnit's return value rather than assuming success). Re-check this comment whenever a
#// take-control-immune unit costing 3 or less is printed.
#//
#// SOR_063 Cloud City Wing Guard costs exactly 3 and is 2/4 → 0/2 under two Weakness tokens (power
#// floors at 0). Board after: Palpatine at P1 idx 0, the stolen unit appended at idx 1.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_110
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_110
P1GROUNDARENAUNIT:1:CARDID:SOR_063
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:0
P1GROUNDARENAUNIT:1:HP:2

---

# WhenPlayed_Decline_NoControlChange_NoTokens
#// The "you may" decline: the unit stays with its controller and receives nothing.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_110
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:4

---

# WhenPlayed_Offer_ExcludesFriendlyAndTooExpensive
#// OFFER cell, left PENDING so the pool itself is inspected. Two exclusions proven at once against an
#// otherwise-identical board:
#//   • SOR_046 (cost 4) — one over the threshold, so the cost gate is load-bearing.
#//   • P1's own SOR_095 (cost 2, would otherwise qualify) — the "enemy" gate is load-bearing.
#// Two legal targets remain so the pool cannot collapse to a single auto-resolving entry.
#// Layout — P1: SOR_095 idx0, Palpatine idx1 (played). P2: SOR_063 idx0, SEC_080 idx1, SOR_046 idx2.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_110
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_063:1:0 SEC_080:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# WhenPlayed_OnlyCostFourEnemy_NoPrompt
#// Boundary lower half, isolated: with ONLY a cost-4 enemy on the board there is no legal target at
#// all, so the ability raises no prompt rather than offering something it cannot use.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_110
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# WhenPlayed_DarksaberLeaderUnit_NotOffered
#// The NON-LEADER gate, and specifically that it reads the LIVE object rather than printed CardType.
#// SEC_111 Jar Jar Binks is a printed "Unit" costing 2 — squarely inside the pool — but ASH_135 The
#// Darksaber makes its host a LEADER UNIT ("Attached unit is a leader unit"). A printed-CardType check
#// would happily offer him; IsLeaderUnit must exclude him.
#// This is the only formulation that isolates the leader gate: a DEPLOYED leader would also be excluded
#// by cost (every leader costs well over 3), so it proves nothing on its own.
#// Two other legal enemies keep the pool inspectable.
#// Layout — P2: SEC_111+Darksaber idx0 (excluded), SEC_080 idx1, SOR_063 idx2.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_110
WithP2GroundArena: [SEC_111:1:0 SEC_080:1:0 SOR_063:1:0]
WithP2GroundArenaUpgrade: 0:ASH_135

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-1&theirGroundArena-2

---

# WhenPlayed_NoEnemyUnits_NoPrompt
#// No valid target at all — the ability is skipped cleanly with no dangling decision.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_110

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_110

---

# WhenPlayed_TwoWeaknessAreLethal_ShrinkDefeatToOwnersDiscard
#// The two tokens compound into a defeat, and the defeat resolves OWNERSHIP correctly.
#// LOF_247 Gungan Warrior (cost 3) is 3/2 → 1/0 under two Weakness tokens. Zero remaining HP has no
#// state-based defeat of its own, so this only works if the shrink sweep runs after attaching.
#// Crucially the unit is P1-CONTROLLED at the moment it dies but still P2-OWNED, so it must land in
#// P2's discard, not P1's — the classic owner-vs-controller split.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_110
WithP2GroundArena: LOF_247:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_110
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:0

---

# WhenPlayed_ExactlyTwoTokens_NotOne
#// Quantity discrimination on "2 Weakness tokens". SEC_080 is 3/3 → 1/1 with two tokens; a single
#// token would leave it 2/2, so the stat pair pins the count independently of UPGRADECOUNT.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_110
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:HP:1

---

# ControlChangeIsPermanent_SurvivesRegroup
#// DURATION cell, inverted: the text names no duration, so the control change is PERMANENT and must NOT
#// be reverted at the regroup phase. If the implementation reached for the TEMPORARY_STEAL marker
#// (LOF_189 Liberated by Darkness / SOR_224 Change of Heart), RegroupPhaseStart would hand the unit
#// back and this is the only section that would notice.
#// Passing through regroup into the next action phase also proves the Weakness tokens persist.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_110
WithP1Deck: [SOR_095 SEC_080 SOR_046]
WithP2Deck: [SOR_095 SEC_080 SOR_046]
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_063
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:0
P1GROUNDARENAUNIT:1:HP:2
P2GROUNDARENACOUNT:0
