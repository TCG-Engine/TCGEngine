#// LOF_009 Darth Maul: "Deal 1 damage to a unit and 1 damage to a DIFFERENT unit." (front Action [Exhaust, use
#// the Force] and deployed On Attack). The damage is mandatory (lof/DarthMaul_SithRevealed.md).
#//
#// FIXED 2026-09-13 (all RED sections green; full suite 11895/0): cards/lof/DarthMaul_SithRevealed.php — both
#//   handlers read the first target's UID BEFORE the damage.
#//
#// FOUND BY: writing the retro #5 cases (2026-09-13); a probe of Maul attacking beside The Mandalorian.
#//
#// ★ WAS RED (3 sections) — candidate engine bug, the defeat-shift family (memory "multi-unit-debuff-loop-defeat-
#//   shift": a mid-loop death shifts mzIDs). Both handlers in cards/lof/DarthMaul_SithRevealed.php (LOF_009#0
#//   front, LOF_009#2 deployed) deal the first 1 damage and only THEN read the first target's UniqueID via
#//   GetZoneObject($lastDecision). When that ping DEFEATS the unit, the mzID already points at the unit that
#//   slid into its slot, so THAT unit is excluded as "the same unit". Probe 2026-09-13, P2 holding a Marine on
#//   1 remaining HP (theirGroundArena-0) and an Acolyte (theirGroundArena-1):
#//     - deployed: the second pick offers ONLY Maul himself (the Acolyte is gone from it), and the damage is
#//       mandatory, so Maul is forced to damage himself;
#//     - front: the second pick is skipped entirely (nothing left after excluding the Acolyte), so the Acolyte
#//       never takes its 1.
#//   Fix shape (APPLIED 2026-09-13): read the UniqueID BEFORE dealing the
#//   damage.
#//
#// Cards: SOR_095 Battlefield Marine 3/3 seeded with 2 damage (1 remaining) · SEC_028 Trayus Acolyte 2/4.
#//
# RED_Deployed_FirstPingDefeatsTheMarine_TheAcolyteIsStillADifferentUnit
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:2
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# RED_Deployed_FirstPingDefeatsTheMarine_SecondPingOnTheAcolyte_MaulUnharmed
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:2
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_028
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:5

---

# RED_Front_FirstPingDefeatsTheMarine_TheAcolyteTakesTheSecond
#// The Acolyte is the only unit left, so the second pick auto-resolves onto it.
## GIVEN
CommonSetup: brk/bbk/{myLeader:LOF_009;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_095:1:2
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_028
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# CONTROL_Deployed_FirstPingDoesNotDefeat_TheAcolyteIsOffered
#// Same board with the Marine at full HP: nothing shifts, and the Acolyte (theirGroundArena-1) is offered.
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-1
