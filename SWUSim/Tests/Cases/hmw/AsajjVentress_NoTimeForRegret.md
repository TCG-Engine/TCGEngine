# Front_RaidBecomesRestore
#// HMW_001 Asajj Ventress, No Time For Regret — Leader (Ground) 3/6, deploy 5, [Vigilance][Aggression],
#// Force/Night, unique.
#// FRONT:  Action [Exhaust]: Attack with a unit. For this attack replace any Raid it has or gains with
#//         Restore, or vice versa.
#// EPIC:   Epic Action: If you control 5 or more resources, deploy this leader.
#// DEPLOY: Restore 2. / Action: Attack with a unit. For this attack, replace any Raid it has or gains
#//         with Restore, or vice versa.
#// COVERAGE (per SIDE, summed — floor 4 + 7 = 11):
#//   FRONT   offer=NoEligibleAttacker_ActionStillFizzles (the attacker pool, and the CR 6.4.587.c
#//                 call) + Honnah_BothKeywords_TheDirectionChoiceIsOffered (the DIRECTION menu, left
#//                 pending — both options present and nothing else) + the P1NODECISION on
#//                 Front_RaidBecomesRestore (a one-keyword unit must NOT be asked) ·
#//           decline=N/A — no "you may" on either side; neither the Action nor the direction is
#//                 declinable once taken ·
#//           boundary=Front_RaidBecomesRestore + Front_RestoreBecomesRaid (the two AUTO-RESOLVED
#//                 directions, on units carrying only one of the keywords) +
#//                 Honnah_BothKeywords_ReplaceRaidWithRestore_Restore4 /
#//                 Honnah_BothKeywords_ReplaceRestoreWithRaid_Raid4 / _UnaidedBaseline — the THREE
#//                 outcomes available on one symmetric unit (Restore 4 / Raid 4 / unchanged), which is
#//                 the whole content of the 2026-09-08 ruling and the only shape that separates a
#//                 CHOICE from an exchange +
#//                 the ASYMMETRIC pair, each with an unaided baseline on the identical board and each
#//                 taking the direction that replaces its GRANTED half:
#//                 Asymmetric_RaidOneRestoreTwo_ReplaceRestore_* (grant on the Restore side) and its
#//                 mirror Asymmetric_RaidTwoRestoreOne_ReplaceRaid_* (grant on the Raid side) +
#//                 Front_GainedRestoreIsReplacedToo (the "or GAINS" half: the replaced keyword is one
#//                 the unit never printed) +
#//                 UpgradeGranted_RaidOneRestoreTwo_ReplaceRestore_* (the same shape with the gained
#//                 half coming from an UPGRADE instead of a unit aura — a different loop inside the
#//                 same conditional function, and the only sections here that walk it) +
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
#// ⚠ PREVIEW SET — HMW is absent from card-specific-rulings.md. Readings taken from the CR + analogues,
#// except the first, which is a user ruling:
#//   • ★ USER RULING 2026-09-08: "replace ... or vice versa" is a CHOICE OF DIRECTION, not a
#//     simultaneous exchange. The player picks which keyword is replaced. This file previously encoded
#//     the exchange reading, under which LAW_050 Honnah (printed Raid 2 AND Restore 2) came out
#//     UNCHANGED and the Action did nothing at all on her — a section named
#//     Control_SymmetricKeywords_SwapIsANoOp asserted exactly that, and is now deleted, superseded by
#//     the four Honnah_BothKeywords_* sections.
#//   • "has or GAINS" is why the replacement is a live recomputation rather than a snapshot: a Raid or
#//     Restore acquired after the attack begins is replaced too. Front_GainedRestoreIsReplacedToo
#//     covers the "gains" half — the Restore there comes from Mother Talzin's aura, not from print.
#//   • With NEITHER keyword there is no direction to choose, so no prompt is raised and a keyword gained
#//     later in that attack is not replaced. Flagged in the card file as the one open question.
#//   • The deployed Action has NO printed [Exhaust] where the front side does. That matches the
#//     engine's existing deployed-leader convention (costKind 'none'), but it is the one thing on this
#//     card worth checking against the printed card when HMW leaves preview.
#//
#// THE POSITIVE, RAID -> RESTORE. Mother Talzin is a 3/4 with printed Raid 1. Attacking normally she
#// hits the base for 4 and heals nothing; under Ventress's Action she hits for 3 and heals 1.
#// ⚠ P1NODECISION IS THE OTHER HALF OF THE RULING. She has Raid and no Restore, so there is only one
#// direction that does anything and the direction prompt must NOT appear — the house rule bans a
#// one-answer question. The Honnah_* sections below assert the opposite on a unit with both keywords.
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
P1NODECISION

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

# Front_GainedRestoreIsReplacedToo
#// THE "or GAINS" HALF. SOR_157 Cantina Braggart is a 0/3 with printed Raid 2 and no printed Restore;
#// Mother Talzin standing beside it GRANTS it Restore 1 (an acquired keyword, not a printed one), so it
#// attacks as Raid 2 / Restore 1.
#// Replace Restore With Raid: the GRANTED Restore 1 is what gets replaced, giving Raid 2 + 1 = 3 and no
#// Restore. It deals power 0 + Raid 3 = 3 and heals nothing.
#// ⚠ THE DISCRIMINATION IS THE DAMAGE. An implementation that replaced only PRINTED keywords would
#// leave the granted Restore alone: Raid 2, Restore 1 — 2 damage and a heal. The heal difference alone
#// would not separate them (this direction heals 0 either way once Restore is spent), so P2BASEDMG is
#// the number that carries this section.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_157:1:0 HMW_039:1:0]
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Replace_Restore_With_Raid
## EXPECT
P2BASEDMG:3
P1BASEDMG:5

---

# Asymmetric_RaidOneRestoreTwo_ReplaceRestore_BecomesRaidThree
#// AN ASYMMETRIC BOARD, replacing the side the GRANT is on. No printed card carries an asymmetric
#// Raid/Restore pair — every printed one is 1/1 or 2/2 — so the board builds it: LAW_090 Toydarian
#// Technician (2/3) prints Raid 1 / Restore 1 and Mother Talzin GRANTS it a second Restore, making it
#// Raid 1 / Restore 2.
#// Replace Restore With Raid: Raid 1 + 2 = 3, no Restore. It deals 2 + 3 = 5 and heals nothing.
#// ⚠ Its mirror below (Asymmetric_RaidTwoRestoreOne_*) takes the OTHER direction on a board whose grant
#// sits on the RAID side, so between them each direction is exercised against a granted value — and
#// neither direction can be the one that happens to be right by accident.
#// DISCRIMINATION: ignoring the granted Restore would give Raid 2 and 4 damage, not 5.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [LAW_090:1:0 HMW_039:1:0]
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Replace_Restore_With_Raid
## EXPECT
P2BASEDMG:5
P1BASEDMG:5

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

# UpgradeGranted_RaidOneRestoreTwo_ReplaceRestore_BecomesRaidThree
#// THE THIRD DISPATCH PATH for the gained half. The asymmetric pairs above build their extra keyword
#// from a UNIT AURA (Mother Talzin / Hondo Ohnaka), which is the friendly-unit loop in
#// GetConditionalKeyword_Restore_Value; this one builds it from an UPGRADE, which is a different loop
#// in the same function (GetUpgradesOnUnit). The swap is computed from the FINAL value, so it must not
#// care which loop contributed it — and nothing else in this file walks the upgrade path.
#// TWI_141 Soldier of the 501st is a 1/3 with printed Raid 1 and no Restore; SOR_070 Devotion attached
#// to it grants Restore 2, making it Raid 1 / Restore 2. Replace Restore With Raid turns that into
#// Raid 1 + 2 = 3 with no Restore: it deals 2 + 3 = 5 to the enemy base and heals nothing.
#// DISCRIMINATION: ignoring the upgrade-granted Restore would give Raid 1 and 3 damage, not 5.
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
- P1>AnswerDecision:Replace_Restore_With_Raid
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_141
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:4
P2BASEDMG:5
P1BASEDMG:5

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

# Asymmetric_RaidTwoRestoreOne_ReplaceRaid_BecomesRestoreThree
#// THE MIRROR, so neither direction can be the accidental one. Same Toydarian Technician
#// (Raid 1 / Restore 1), but the extra point is on the RAID side this time: SEC_140 Hondo Ohnaka grants
#// "each other friendly unit Raid 1", making it Raid 2 / Restore 1.
#// Replace Raid With Restore: no Raid at all, Restore 1 + 2 = 3. It deals its bare power 2 and heals 3.
#// DISCRIMINATION: ignoring Hondo's granted Raid would leave Raid 1 (3 damage) and Restore 2 (heal 2),
#// so both numbers separate the readings.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [LAW_090:1:0 SEC_140:1:0]
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Replace_Raid_With_Restore
## EXPECT
P2BASEDMG:2
P1BASEDMG:2

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

---

# Honnah_BothKeywords_ReplaceRestoreWithRaid_Raid4
#// ★ THE CARD THAT SETTLES THE READING. LAW_050 Honnah, OINK! SQUEE! is a 3/5 ground unit printed with
#// BOTH Raid 2 AND Restore 2 — the only shape where "replace any Raid it has or gains with Restore, or
#// vice versa" has to mean something other than an exchange.
#//
#// USER RULING 2026-09-08: it is a CHOICE OF DIRECTION, not a simultaneous exchange. You pick ONE
#// keyword and replace it with the other for this attack. So Honnah is either Raid 4 (her Restore 2
#// becomes Raid) or Restore 4 (her Raid 2 becomes Restore) — never unchanged.
#//
#// This direction: Replace Restore With Raid. Raid 2 + the 2 that used to be Restore = Raid 4, and no
#// Restore at all. She hits the base for power 3 + Raid 4 = 7 and heals NOTHING, so P1's base stays on
#// the 6 damage it started with.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_050:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Replace_Restore_With_Raid
## EXPECT
P2BASEDMG:7
P1BASEDMG:6
P1LEADER:EXHAUSTED

---

# Honnah_BothKeywords_ReplaceRaidWithRestore_Restore4
#// The OTHER direction on the identical board — this pair is the whole point of the ruling. Replace
#// Raid With Restore: Restore 2 + the 2 that used to be Raid = Restore 4, and no Raid. She hits for her
#// bare power 3 and heals 4, taking P1's base from 6 damage to 2.
#//
#// Both sections attack the SAME board with the SAME unit and differ ONLY in the answer, so each of the
#// four numbers here separates this direction from the other one — and both separate it from the
#// unaided baseline below (5 / 4).
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_050:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Replace_Raid_With_Restore
## EXPECT
P2BASEDMG:3
P1BASEDMG:2
P1LEADER:EXHAUSTED

---

# Honnah_BothKeywords_UnaidedBaseline
#// THE CONTROL, on the identical board. Without Ventress's Action, Honnah attacks with both keywords
#// intact: power 3 + Raid 2 = 5 to the enemy base, Restore 2 healing P1's base from 6 to 4.
#//
#// ⚠ This is the section that used to say the swap was a NO-OP on a symmetric unit (it asserted these
#// same numbers THROUGH the Action). Under the ruling those two boards must now differ, which is why
#// the baseline had to become a plain attack.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_050:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5
P1BASEDMG:4
P1LEADER:READY

---

# Honnah_BothKeywords_TheDirectionChoiceIsOffered
#// THE OFFER, not the branch. Answering a direction proves the branch resolves; only leaving the
#// decision pending proves BOTH directions are on the menu and that nothing else is. A card with one
#// keyword must never see this prompt (Front_RaidBecomesRestore / Front_RestoreBecomesRaid both assert
#// P1NODECISION for exactly that), so the two options existing together is the whole ruling.
## GIVEN
CommonSetup: brk/ggw/{myLeader:HMW_001; myResources:6; myBaseDamage:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_050:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HASDECISION
P1OPTIONHAS:Replace_Raid_With_Restore
P1OPTIONHAS:Replace_Restore_With_Raid
P2BASEDMG:0
