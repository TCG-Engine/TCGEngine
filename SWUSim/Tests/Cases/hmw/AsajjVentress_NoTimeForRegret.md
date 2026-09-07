# Front_RaidBecomesRestore
#// HMW_001 Asajj Ventress, No Time For Regret — Leader (Ground) 3/6, deploy 5, [Vigilance][Aggression],
#// Force/Night, unique.
#// FRONT:  Action [Exhaust]: Attack with a unit. For this attack replace any Raid it has or gains with
#//         Restore, or vice versa.
#// EPIC:   Epic Action: If you control 5 or more resources, deploy this leader.
#// DEPLOY: Restore 2. / Action: Attack with a unit. For this attack, replace any Raid it has or gains
#//         with Restore, or vice versa.
#// COVERAGE (per SIDE, summed — floor 4 + 7 = 11):
#//   FRONT   offer=NoEligibleAttacker_ActionStillFizzles (the pool, and the CR 6.4.587.c call) ·
#//           decline=N/A — no "you may" on either side; the Action itself is the only choice ·
#//           boundary=Front_RaidBecomesRestore + Front_RestoreBecomesRaid (BOTH directions — "or vice
#//                 versa") + the ASYMMETRIC pairs, each with an unaided baseline on the identical
#//                 board: Asymmetric_RaidOneRestoreTwo_* and its mirror Asymmetric_RaidTwoRestoreOne_*
#//                 (both values non-zero AND different, so the exchange must move them in OPPOSITE
#//                 directions — the only shape that separates an exchange from a one-way conversion) +
#//                 Front_BothKeywordsExchange (the same Raid 2 / Restore 1 total reached the other way
#//                 round — Raid PRINTED and Restore GRANTED, where the asymmetric mirror has Raid
#//                 part-granted and Restore printed) +
#//                 UpgradeGranted_RaidOneRestoreTwo_* (the same asymmetric shape with the gained half
#//                 coming from an UPGRADE instead of a unit aura — a different loop inside the same
#//                 conditional function, and the only sections here that walk it) +
#//                 Control_SymmetricKeywords_* (Raid 2 / Restore 2 -> unchanged; see its note — it is
#//                 the ONLY section that reds when the delta is written as `+= other` rather than as an
#//                 exchange, and correctly stays green when the swap is removed entirely) +
#//                 SwapExpires_WithinTheSamePhase (the duration cell that DISCRIMINATES — see its
#//                 note; Front_SwapExpires_TheNextAttackIsNormal crosses a round boundary and so
#//                 cannot tell "this attack" from "this phase") ·
#//           control=Front_LeaderExhausts (the printed cost) ·
#//   DEPLOY  offer=Deployed_NoEligibleAttacker_ActionNotOffered (the FREE-cost gate — the deployed
#//                 side must NOT be offerable when the front side deliberately still is) ·
#//           boundary=Deployed_Restore2_HealsOnAttack (the passive) +
#//                 Deployed_SwapsHerOwnRestoreIntoRaid ("a unit", not "another unit") ·
#//           control=Deployed_ActionDoesNotExhaustHer ·
#//   BOTH    reqboundary=RequestBoundary_SwapSurvivesTheAttackerChoice ·
#//           epic=Epic_DeployAtFiveResources + Epic_BlockedAtFourResources ·
#//           modes=2P ONLY. Neither side names a player, and neither uses "friendly" or "enemy" —
#//                 "a unit" is resolved from the acting player's own arenas — so Premier, Twin Suns and
#//                 Team Suns share one code path and a far-seat section could never fail.
#//
#// ⚠ PREVIEW SET — HMW is absent from card-specific-rulings.md. Readings taken from the CR + analogues:
#//   • "replace ... or vice versa" is a bidirectional EXCHANGE, not a one-way conversion. A unit with
#//     both keywords swaps both values.
#//   • "has or GAINS" is why the swap is a live recomputation rather than a snapshot: a Raid or Restore
#//     acquired after the attack begins is swapped too. Front_BothKeywordsExchange covers the "gains"
#//     half — the Restore there comes from Mother Talzin's aura, not from print.
#//   • The deployed Action has NO printed [Exhaust] where the front side does. That matches the
#//     engine's existing deployed-leader convention (costKind 'none'), but it is the one thing on this
#//     card worth checking against the printed card when HMW leaves preview.
#//
#// THE POSITIVE, RAID -> RESTORE. Mother Talzin is a 3/4 with printed Raid 1. Attacking normally she
#// hits the base for 4 and heals nothing; under Ventress's Action she hits for 3 and heals 1.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_039:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:3
P1BASEDMG:4
P1LEADER:EXHAUSTED

---

# Front_RestoreBecomesRaid
#// THE OTHER DIRECTION ("or vice versa"). LOF_253 Longbeam Cruiser is a 6/6 with printed Restore 1 and
#// no Raid. Attacking normally it hits for 6 and heals 1; under the Action it hits for 7 and heals
#// nothing. A one-way Raid->Restore implementation passes the section above and fails only here.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LOF_253:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P2BASEDMG:7
P1BASEDMG:5

---

# Front_BothKeywordsExchange
#// THE EXCHANGE, and the "or GAINS" half. SOR_157 Cantina Braggart is a 0/3 with printed Raid 2;
#// Mother Talzin standing beside it GRANTS it Restore 1 (an acquired keyword, not a printed one). So it
#// attacks as Raid 2 / Restore 1 normally — 2 damage, heal 1 — and under Ventress as Raid 1 /
#// Restore 2: 1 damage, heal 2. Both numbers move, in opposite directions, which no one-way conversion
#// and no snapshot-at-declaration can reproduce.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_157:1:0 HMW_039:1:0]
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:1
P1BASEDMG:3

---

# Control_SymmetricKeywords_SwapIsANoOp
#// THE CONTROL. LAW_050 Honnah, OINK! SQUEE! is a 3/5 with printed Raid 2 AND Restore 2, so the
#// exchange maps each value onto an identical one and the attack is byte-for-byte the same as an
#// unaided one: 3 + Raid 2 = 5 to the enemy base, Restore 2 off P1's.
#// Its job is the INVERSE of every other swap section here. Those catch a swap that fails to happen;
#// this catches one that happens WRONG — a delta written as `+= other` instead of `other - this` turns
#// Honnah into Raid 4 / Restore 4 and is invisible on every asymmetric board, because on a unit with
#// only one of the two keywords the additive and the exchange readings agree.
#// The partner section below runs the identical board WITHOUT the Action, so the two numbers are pinned
#// as genuinely unchanged rather than merely asserted.
#// MEASURED: of the four deltas mutated in (additive / one-way / sign-inverted / removed), the additive
#// one is the ONLY one this section catches, and it is the only section that catches it. Removing the
#// swap entirely correctly leaves it GREEN — that is the property being asserted, not a weakness.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_050:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:5
P1BASEDMG:3
P1LEADER:EXHAUSTED

---

# Control_SymmetricKeywords_UnaidedBaseline
#// The baseline half of the control: the same Honnah attacking with no Action taken. Identical numbers
#// to the section above — which is what makes "the swap was a no-op" a measurement rather than a claim.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_050:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5
P1BASEDMG:3
P1LEADER:READY

---

# Asymmetric_RaidOneRestoreTwo_BecomesRaidTwoRestoreOne
#// THE SHARPEST SHAPE ON THIS CARD: both values are non-zero AND different, so the exchange has to move
#// them in OPPOSITE directions in a single attack. No printed card carries an asymmetric Raid/Restore
#// pair — every printed one is 1/1 or 2/2 — so the board builds it: LAW_090 Toydarian Technician prints
#// Raid 1 / Restore 1, and Mother Talzin standing beside it GRANTS a second Restore, making it
#// Raid 1 / Restore 2. That also exercises "has or GAINS" on the half that is gained, not printed.
#// Unaided the Technician deals 2 + 1 = 3 and heals 2. Under Ventress it is Raid 2 / Restore 1: it
#// deals 4 and heals 1. Both numbers move, in opposite directions.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [LAW_090:1:0 HMW_039:1:0]
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:4
P1BASEDMG:4

---

# Asymmetric_RaidOneRestoreTwo_UnaidedBaseline
#// The baseline for the pair above, on the identical board: with no Action the Technician is
#// Raid 1 / Restore 2 and deals 3 while healing 2. Without this section the numbers 4 and 4 above are
#// unanchored — they only mean something against what the same board does unswapped.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [LAW_090:1:0 HMW_039:1:0]
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1BASEDMG:3

---

# UpgradeGranted_RaidOneRestoreTwo_BecomesRaidTwoRestoreOne
#// THE THIRD DISPATCH PATH for the gained half. The asymmetric pairs above build their extra keyword
#// from a UNIT AURA (Mother Talzin / Hondo Ohnaka), which is the friendly-unit loop in
#// GetConditionalKeyword_Restore_Value; this one builds it from an UPGRADE, which is a different loop
#// in the same function (GetUpgradesOnUnit). The swap is computed from the FINAL value, so it must not
#// care which loop contributed it — and nothing else in this file walks the upgrade path.
#// TWI_141 Soldier of the 501st is a 1/3 with printed Raid 1 and no Restore; SOR_070 Devotion attached
#// to it grants Restore 2, making it Raid 1 / Restore 2. Under Ventress that becomes Raid 2 /
#// Restore 1: it deals 2 + 2 = 4 to the enemy base and heals 1 instead of 2.
#// ⚠ DEVOTION IS ALSO +1/+1. Its printed text only mentions the Restore grant, but the card carries
#// upgrade power/HP of 1 (CardUpgradePower/CardUpgradeHP), so the host attacks as a 2/4, not a 1/3 —
#// asserted below so the arithmetic here can't silently drift. A first draft of this pair read the
#// text alone, expected 3 and 2, and both sections went red against a CORRECT engine.
#// ⚠ A Battlefield Marine shares the arena purely so the attacker choose stays INTERACTIVE — with a
#// single eligible attacker the offer auto-resolves and the answer line below would be a spare answer
#// eaten by the next prompt.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [TWI_141:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:SOR_070
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_141
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:4
P2BASEDMG:4
P1BASEDMG:4

---

# UpgradeGranted_RaidOneRestoreTwo_UnaidedBaseline
#// Baseline for the pair above on the identical board: unaided the Devotion-wearing Soldier is a 2/4
#// with Raid 1 / Restore 2, so it deals 2 + 1 = 3 and heals 2. This is also what pins that Devotion's
#// grant actually landed — without it the Soldier would heal nothing and P1's base would stay on 5.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [TWI_141:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:SOR_070
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1BASEDMG:3

---

# Asymmetric_RaidTwoRestoreOne_BecomesRaidOneRestoreTwo
#// THE MIRROR, so neither direction can be the accidental one. Same Toydarian Technician
#// (Raid 1 / Restore 1), but the extra point is on the RAID side this time: SEC_140 Hondo Ohnaka grants
#// "each other friendly unit Raid 1", making it Raid 2 / Restore 1.
#// Unaided it deals 2 + 2 = 4 and heals 1. Under Ventress it is Raid 1 / Restore 2: it deals 3 and
#// heals 2 — the exact numbers the previous pair produces with the roles reversed, which is what an
#// exchange (rather than any one-way rule) predicts.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [LAW_090:1:0 SEC_140:1:0]
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:3
P1BASEDMG:3

---

# Asymmetric_RaidTwoRestoreOne_UnaidedBaseline
#// Baseline for the mirror: Raid 2 / Restore 1 unaided deals 4 and heals 1.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [LAW_090:1:0 SEC_140:1:0]
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:4
P1BASEDMG:4

---

# Front_SwapExpires_TheNextAttackIsNormal
#// THE DURATION CELL. "For THIS attack" — the same unit attacking again in the next round, without the
#// Action, is back to its printed Raid 1: 4 damage to the base and no heal. Asserted on the OUTCOME
#// because Raid has no standalone stat readout; the pair with Front_RaidBecomesRestore is what makes it
#// discriminating (same board, same attacker, only the Action differs).
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_039:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:7
P1BASEDMG:4

---

# SwapExpires_WithinTheSamePhase
#// THE DURATION CELL THAT ACTUALLY DISCRIMINATES. "For THIS attack", not for the phase — and the
#// round-crossing section above CANNOT tell those apart, because a phase-duration effect expires at the
#// round boundary too. Measured, not hypothetical: switching the marker to SWU_DUR_PHASE left that
#// section green and this one is what was written to catch it.
#// Ventress (deployed, so her Action does not exhaust her) sends Mother Talzin in with the swap: 3 to
#// the enemy base and 1 healed off P1's. SHD_182 Bravado then readies Talzin inside the SAME phase and
#// she attacks again unaided — 4 to the base and no heal. A phase-long marker would deal 3 and heal
#// again, moving BOTH numbers.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001:1:1; myResources:6; myBaseDamage:5; myhandCardIds:SHD_182}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_039:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:7
P1BASEDMG:4

---

# Front_NoEligibleAttacker_ActionStillFizzles
#// CR 6.4.587.c — the front cost is STATE-CHANGING ([Exhaust]), so the Action stays usable with no
#// eligible attacker and simply does nothing. The leader still exhausts and no decision is left
#// pending. This is the deliberate opposite of the deployed side below.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1NODECISION
P2BASEDMG:0

---

# Deployed_Restore2_HealsOnAttack
#// The deployed PASSIVE. Restore 2 is generated ($Restore_Cards['HMW_001'] => 2): the 3/6 leader unit
#// attacking the base heals 2 off a base on 5 damage and deals 3.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001:1:1; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_001
P1BASEDMG:3
P2BASEDMG:3

---

# Deployed_SwapsHerOwnRestoreIntoRaid
#// "Attack with A UNIT", not "another unit" — Ventress may pick HERSELF. Her own Restore 2 then becomes
#// Raid 2, so she hits for 5 instead of 3 and heals nothing instead of 2. That is the exact inverse of
#// the section above on the same board, which is what makes the pair discriminating.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001:1:1; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1BASEDMG:5
P2BASEDMG:5

---

# Deployed_ActionDoesNotExhaustHer
#// The deployed Action carries no [Exhaust] (costKind 'none'), so using it on ANOTHER unit leaves
#// Ventress herself ready. The Talzin she sends in swaps Raid 1 for Restore 1 exactly as on the front.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001:1:1; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_039:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1BASEDMG:4
P2BASEDMG:3
P1GROUNDARENAUNIT:1:CARDID:HMW_001
P1GROUNDARENAUNIT:1:READY

---

# Deployed_NoEligibleAttacker_ActionNotOffered
#// THE FREE-COST GATE, and the asymmetry with the front side. The deployed Action costs NOTHING, so
#// CR 6.4.587.c does not keep it available: with no unit able to attack it must not be offered at all.
#// Ventress is exhausted here, so she cannot be her own attacker and no other unit is in play.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001:0:1; myResources:6}
SkipPreGame: true
P1OnlyActions: true
## WHEN
## EXPECT
P1UNITACTIONSNOT:myGroundArena-0

---

# RequestBoundary_SwapSurvivesTheAttackerChoice
#// The request-boundary cell: the Action queues a choose and the swap marker is applied in the
#// continuation behind it, so nothing may be held in memory across the boundary. Same GIVEN and EXPECT
#// as Front_RaidBecomesRestore with one SimulateRequestBoundary inserted before the answer.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_039:1:0
## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:3
P1BASEDMG:4

---

# Epic_DeployAtFiveResources
#// "Epic Action: If you control 5 or more resources, deploy this leader." The threshold equals the
#// leader's printed cost (5), which is the ENGINE DEFAULT — this card needs no deploy code. The
#// boundary partner below is what actually pins the number.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:5}
P1OnlyActions: true
## WHEN
- P1>DeployLeader
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_001

---

# Epic_BlockedAtFourResources
#// Boundary partner: one under the threshold is a full no-op — no arena unit, Epic still available.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:4}
P1OnlyActions: true
## WHEN
- P1>DeployLeader
## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
