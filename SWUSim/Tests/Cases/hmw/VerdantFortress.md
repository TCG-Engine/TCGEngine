# FriendlyUnitAttacksForOneMore
#// HMW_126 Verdant Fortress (Upgrade, cost 2, [Command][Heroism], Fortification, non-unique) —
#// "Fortify (Attach this to your base, not a unit.) / Attached base gains: 'Friendly units gain Raid 1
#// (They get +1/+0 while attacking.)'"
#//
#// COVERAGE: offer=N/A (structural: the grant selects nothing, and the only pool on the card is the
#//           Fortify base slot, which is generic keyword behaviour pinned by keywords/Fortify.md
#//           AFortifyUpgradeIsNotOfferedAUnitHost / ...TheENEMYBase) ·
#//           decline=N/A (structural: no "may" anywhere; playing an upgrade is not an optional effect) ·
#//           boundary=TwoCopiesStack + StacksWithPrintedRaid (the quantity cells: Raid is a NUMERIC
#//           keyword, so unlike HMW_112's boolean Overwhelm the count and the addition are load-bearing) ·
#//           control=ControlChange_FriendlyMeansTheController (the BASE cannot change hands, but the
#//           UNITS it reads can — owner≠controller on both sides) ·
#//           reqboundary=RequestBoundary_TheGrantIsRecomputedAfterTheBoundary ·
#//           modes=2P,TeamSuns — "FRIENDLY units" is relative to the BASE's controller and spans the team
#//           in a 2v2 (TeamSuns_ATeammatesUnitsGainRaid). No player reference, so no Twin Suns section.
#//
#// FORTIFY itself needs no code (HMW_126 is in $Fortify_Cards). The grant is base-hosted and CONTINUOUS —
#// the exact twin of HMW_112 Military Academy, with Raid 1 in place of Overwhelm. Nothing is stored;
#// GetConditionalKeyword_Raid_Value asks the board on every read.
#//
#// This section drives the REAL dispatch path: the Fortress is PLAYED from hand (Fortify routes it to the
#// base) and then a friendly unit attacks. Battlefield Marine is 3/3, so the base takes 3 + Raid 1 = 4.

## GIVEN
CommonSetup: ggw/ggw/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_126
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEUPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P2BASEDMG:4

---

# RaidIsOnlyWhileAttacking_PowerAtRestIsPrinted
#// HMW_126 — Raid is "+1/+0 WHILE ATTACKING" (CR 7.5.8.a/c), not a flat +1/+0. The attack deals 4, and
#// after the attack the unit's power reads its printed 3 again. An implementation that stamped a
#// permanent +1/+0 buff instead of granting the keyword passes the damage assertion and fails the POWER
#// one — so the pair is what distinguishes "gains Raid 1" from "gets +1/+0".

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_126
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:POWER:3

---

# SpaceUnitsAreFriendlyUnitsToo
#// HMW_126 — "friendly units" names no arena, so a SPACE unit gains it as well. A grant written against
#// the ground arena only (the ZoneSearch-one-arena shortcut) passes every ground section and fails here.
#// Alliance X-Wing is 2/3 → 2 + Raid 1 = 3.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_126
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Raid
P2BASEDMG:3

---

# EnemyUnitsDoNotGainRaid
#// HMW_126 — the CONTROLLER negative. "FRIENDLY units" is relative to the base carrying the Fortress, so
#// the opponent's units gain nothing. Here the OPPONENT attacks: Imperial Dark Trooper is 3/3 and must
#// deal exactly 3. A grant written as "each unit in play" passes every other section and deals 4 here.
#// P1's own unit gaining Raid in the same board proves the Fortress is live, so the 3 is not a dead grant.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
WithActivePlayer: 2
WithP1BaseUpgrade: HMW_126
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1BASEDMG:3

---

# StacksWithPrintedRaid
#// ⚠ HMW_126 — QUANTITY discrimination. CR 7.5.8.b: "Multiple instances of Raid stack … the numerals are
#// added together." Captain Tarpals is 0/2 with PRINTED Raid 2, so with the Fortress he is Raid 3 and his
#// damage IS his Raid value: 3. A grant that took the MAX of the instances (the shape the TurnEffect
#// grant path uses) reads 2; a grant that replaced the printed value reads 1. Only additive gives 3.
#// Seeded into the arena, so his Shielded never triggers and cannot absorb anything.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_126
WithP1GroundArena: HMW_254:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# TwoCopiesStack
#// ⚠ HMW_126 — QUANTITY discrimination, the per-COPY cell. The Fortress is NON-unique, so a player can
#// attach two, and each one is a separate source granting its own "Raid 1" — CR 7.5.8.b adds them: Raid 2.
#// This is the deliberate difference from HMW_112 Military Academy, whose Overwhelm is boolean (CR 7.5.7.b,
#// a second copy adds nothing). A boolean "is it on the base?" read passes every other section in this
#// file and deals 4 here instead of 3 + 2 = 5.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_126,HMW_126
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEUPGRADECOUNT:2
P2BASEDMG:5

---

# GrantDiesWithTheUpgrade
#// ⚠ HMW_126 — THE REVOCATION TEST. SOR_251 Confiscate defeats the Fortress (the only upgrade in play,
#// so its target auto-resolves), and the unit must LOSE Raid: it then attacks for its printed 3. A grant
#// registered once on the unit and never revoked looks identical in every other section. It holds here by
#// construction (a live board read, no stored state) — and "by construction" is exactly what needs a test.
#// ⚠ Measured 2026-09-10: deleting the `removed` filter in _SWUCountBaseUpgrades stays GREEN, because
#// SWUDefeatUpgrade SPLICES the subcard out rather than flagging it. So this section does not guard that
#// filter; it guards against any future implementation that STORES the grant on the unit.

## GIVEN
CommonSetup: ggw/ggw/{
  myResources:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_251
WithP1BaseUpgrade: HMW_126
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEUPGRADECOUNT:0
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P2BASEDMG:3

---

# RequestBoundary_TheGrantIsRecomputedAfterTheBoundary
#// HMW_126 — the REQUEST-BOUNDARY cell in its no-decision form. The Fortress is attached by one action
#// and read during LATER ones, each in a fresh process, so it must be recomputed from the board rather
#// than cached at attach time. The seeded unit attacks for 4 after the boundary, and a unit PLAYED after
#// the boundary still gains Raid — a CONSTANT ability reaches units that enter play later, which is the
#// difference from a lasting effect like SOR_154 Rallying Cry (CR 7.7.3.d, Lasting Effects, whose own
#// example is Rallying Cry reaching only the units in play when it resolved).

## GIVEN
CommonSetup: ggw/ggw/{
  myResources:4
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_126
WithP1Hand: SOR_095
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid

---

# ControlChange_FriendlyMeansTheController
#// HMW_126 — the CONTROL cell. The base cannot change hands, but the units it reads can, and "friendly"
#// is CONTROL, not ownership. P1 controls an Imperial Dark Trooper that P2 OWNS (it gains Raid); P2
#// controls a Battlefield Marine that P1 OWNS (it does not). A grant keyed on Owner inverts both
#// assertions, so the pair pins the direction.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_126
WithP1GroundArenaControlled: SEC_080:2
WithP2GroundArenaControlled: SOR_095:1

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid

---

# TokenAndLeaderUnitsAreFriendlyUnits
#// HMW_126 — the VALUE-CLASS cell. "Friendly units" includes a TOKEN unit and a deployed LEADER unit —
#// the two classes a hand-rolled ['Unit'] type filter silently drops. Battle Droid token (1/1, index 0)
#// and the deployed Leia leader unit (appended after the seeded units, index 1) both gain Raid; the token
#// then attacks for 1 + 1 = 2 so at least one class is proven behaviourally, not only by keyword.

## GIVEN
CommonSetup: ggw/ggw/{
  myLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_126
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
P2BASEDMG:2

---

# AUnitThatLostItsAbilitiesCannotGainRaid
#// HMW_126 — interaction with "loses all abilities and can't gain abilities". Unit 0 carries SOR_138
#// Force Lightning's lose-abilities marker, so it gains nothing from the Fortress and attacks for its
#// printed 3; unit 1 is the untouched control and still gains Raid. This rides the central
#// SWUKeywordSuppressed gate in GetKeyword_Raid_Value — guarded here because the Fortress's delta is
#// added inside that function and a future refactor could move it above the gate.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_126
WithP1GroundArena: SOR_095:1:0:SOR_138
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
P2BASEDMG:3

---

# Galen_NamingTheFortress_SwitchesOffTheGrant
#// ⚠ HMW_126 — SEC_046 Galen Erso: "cards with the chosen name lose all abilities". The Fortress's
#// "Attached base gains: …" IS one of its abilities, so naming it switches the grant off — the upgrade
#// stays attached (it is not defeated), but grants nothing. P1 plays Galen and names Verdant Fortress;
#// P2's Battlefield Marine then attacks for its printed 3.
#// Paired with Galen_NamingAnotherCard_GrantStands: same board, a different name, and the grant stands.

## GIVEN
CommonSetup: bbw/ggw/{
  myResources:4
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SEC_046
WithP2BaseUpgrade: HMW_126
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Verdant Fortress
- P2>AttackGroundArena:0:BASE

## EXPECT
P2BASEUPGRADECOUNT:1
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1BASEDMG:3

---

# Galen_NamingTheBase_SwitchesOffTheGrant
#// ⚠ HMW_126 — USER RULING 2026-09-10: a Fortification whose text says "Attached base gains …" is
#// ineffective when Galen names the BASE (the base loses all abilities and can't gain any). Same board as
#// the section above, but Galen names P2's base (Echo Base) instead of the Fortress — the Marine again
#// attacks for its printed 3. The rule lives in _SWUFortifyBlanked; every other Fortify card's pair is in
#// keywords/Fortify_GalenErso.md.

## GIVEN
CommonSetup: bbw/ggw/{
  myResources:4
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SEC_046
WithP2BaseUpgrade: HMW_126
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Echo Base
- P2>AttackGroundArena:0:BASE

## EXPECT
P2BASEUPGRADECOUNT:1
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1BASEDMG:3

---

# Galen_NamingAnotherCard_GrantStands
#// HMW_126 — the control for the section above: identical board and turn sequence, but Galen names a
#// card that is not on the table. The Fortress still grants Raid, so the Marine attacks for 4 — which is
#// what makes the 3 above attributable to the naming, and not to the turn sequence or to Galen's arrival.

## GIVEN
CommonSetup: bbw/ggw/{
  myResources:4
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SEC_046
WithP2BaseUpgrade: HMW_126
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Confiscate
- P2>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Raid
P1BASEDMG:4

---

# TeamSuns_ATeammatesUnitsGainRaid
#// ⚠ HMW_126 — the TEAM SUNS cell, earned by "FRIENDLY units". In a 2v2 a teammate's units are friendly
#// to the base carrying the Fortress, so they gain Raid too. Teams are seat parity (1+3 vs 2+4), so P1's
#// partner is P3. Reading "friendly" as "units you control" passes every other section and fails here;
#// both enemy seats field a unit, so an "everyone in play" grant is caught on the same board.

## GIVEN
CommonSetup: ggw/ggw/{
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_126
WithP3GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP4GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:HASKEYWORD:Raid
P2GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P4GROUNDARENAUNIT:0:NOTKEYWORD:Raid
