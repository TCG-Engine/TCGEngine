# Aura_OtherFriendlyGroundUnitGainsRestoreOne
#// HMW_039 Mother Talzin, Pledged to the Sisterhood — Unit (Ground) 3/4, cost 3,
#// [Vigilance][Aggression], Force/Night, unique.
#// Text: Raid 1
#//       Each other friendly unit gains Restore 1.
#// COVERAGE: offer=N/A — a constant aura grants a keyword to a computed set; nothing is chosen and
#//                 there is no decision to inspect ·
#//           decline=N/A — no "you may" anywhere on the card ·
#//           boundary=TalzinHerselfGainsNothing ("OTHER", and the same section proves her own Raid 1)
#//                 + EnemyUnitsGainNothing + StacksWithPrintedRestore (the aura ADDS to a printed
#//                 Restore rather than replacing it) + SpansTheSpaceArenaToo ·
#//           control=EndsWhenTalzinLeavesPlay + BlankedTalzinGrantsNothing — the aura must RECOMPUTE,
#//                 not stamp: a grant applied once when a unit arrives is indistinguishable from a
#//                 live one in every positive section ·
#//           reqboundary=N/A — a constant ability is recomputed from the board on every read and
#//                 holds no state across a decision; there is no value written before a decision and
#//                 read after one ·
#//           modes=Team Suns (TeammatesUnitGainsRestore) — "each other FRIENDLY unit" is
#//                 team-relative, so the grant must cross to a teammate's board. Twin Suns adds
#//                 nothing beyond that: the card names no player, so a 3-4 seat free-for-all is the
#//                 same code path as Premier.
#//
#// ⚠ PREVIEW SET — HMW is absent from card-specific-rulings.md, so these are CR + analogue readings:
#//   • The textual twins are SEC_047 Coronet and SOR_102 Home One ("Each other friendly unit gains
#//     Restore 1"), so the aura is modelled the same way they are: summed into
#//     GetConditionalKeyword_Restore_Value, recomputed from the live board on every read.
#//   • "FRIENDLY" spans the TEAM, unlike "you control" which is self-only. ⚠ SEC_047/SOR_102/TS26_40
#//     are currently SELF-ONLY on this axis — see the card file's note; HMW_039 is deliberately
#//     team-aware and that difference is raised rather than swept.
#//   • Restore heals the base of the ATTACKING unit's controller ("your base"), which is what makes
#//     the teammate section observable from seat 1.
#//
#// THE POSITIVE. P1's base is on 5 damage; a vanilla Battlefield Marine standing next to Talzin
#// attacks and heals 1 off it. The Marine has no printed Restore of its own.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_039:1:0 SOR_095:1:0]
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P1BASEDMG:4
P2BASEDMG:3

---

# Aura_TalzinHerselfGainsNothing
#// "Each OTHER friendly unit" — Talzin is excluded from her own aura, so her attack heals nothing.
#// The same section verifies her printed Raid 1: 3 power + 1 = 4 damage to the enemy base.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_039:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:5
P2BASEDMG:4

---

# Aura_EnemyUnitsGainNothing
#// "FRIENDLY" — an enemy unit attacking does not heal the enemy base. Without this the grant could be
#// scoped to every unit on the table and nothing else here would notice.
## GIVEN
CommonSetup: brk/ggw/{theirResources:6; theirBaseDamage:5}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: HMW_039:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5

---

# Aura_SpansTheSpaceArenaToo
#// "Each other friendly unit" names no arena, so a SPACE unit gains it as readily as a ground one —
#// Talzin is a ground unit but her aura is not arena-scoped.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_039:1:0
WithP1SpaceArena: SOR_237:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1BASEDMG:4

---

# Aura_StacksWithPrintedRestore
#// The grant ADDS to a printed Restore rather than replacing it — the summing convention SOR_102 Home
#// One and SEC_047 Coronet already use. LOF_253 Longbeam Cruiser prints Restore 1 and nothing else, so
#// under Talzin it heals 2.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_039:1:0
WithP1SpaceArena: LOF_253:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1BASEDMG:3

---

# Aura_EndsWhenTalzinLeavesPlay
#// THE RECOMPUTE CELL. A constant ability is not a stamp applied when the ally arrives — kill Talzin
#// and the Restore is gone on the very next attack. A grant registered once on the ally would pass
#// every positive section in this file and fail only here.
#// P2's 5-power Wampa trades into the 3/4 Talzin, then P1's untouched Marine attacks and heals nothing.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myBaseDamage:5; theirResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [HMW_039:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_164:1:0
## WHEN
- P2>AttackGroundArena:0:0
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1BASEDMG:5

---

# BlankedTalzinGrantsNothing
#// A blanked source grants nothing — the rule the shared Restore loop already applies to every other
#// granting unit. LOF_202 Mind Trick exhausts units with combined power 4 or less and, since P1
#// controls a Force unit (Talzin herself is Force), strips their abilities for the phase. Talzin is
#// 3 power, so she is a legal target; with her abilities gone the Marine's attack heals nothing.
## GIVEN
CommonSetup: brk/ggw/{myResources:12; myBaseDamage:5; myhandCardIds:LOF_202}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_039:1:0 SOR_095:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-
- P1>AttackGroundArena:1:BASE
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1BASEDMG:5

---

# TeamSuns_ATeammatesUnitGainsRestore
#// TEAM SUNS (seats 1+3 vs 2+4). "FRIENDLY" is team-relative, not self-only: Talzin sits on the
#// TEAMMATE's board at seat 3 and still grants Restore 1 to P1's Marine, so P1's own base heals when
#// that Marine attacks. A self-only implementation — which is what the textually identical SEC_047 and
#// SOR_102 do today — cannot pass this, and no other section in the file can see the difference.
#// The ACTOR is seat 1; seat 3 only holds a board.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3GroundArena: HMW_039:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:4
