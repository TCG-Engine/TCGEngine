# PrintedRaidPlusAPhaseGrant_Sum
#// RAID STACKS — CR 7.5.8.b ("Multiple instances of Raid stack … the numerals are added together") and
#// USER RULING 2026-09-10: "Raid does stack. They should be summed as part of the Raid amount calculation."
#// The generated GetKeyword_Raid_Value used to take max(printed, highest TurnEffect grant), so a unit with
#// printed Raid that gained more Raid for the phase kept only the larger of the two.
#//
#// Every section here is built so the OLD answer and the SUMMED answer differ, and each names both.
#//
#// HMW_254 Captain Tarpals is 0/2 with PRINTED Raid 2, so his damage IS his Raid value. Seeded carrying
#// SOR_154 Rallying Cry's "Raid 2 for this phase" token: summed = 4, old max() = 2.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_254:1:0:SOR_154

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:4
P2BASEDMG:4

---

# RallyingCryPlayedOnAPrintedRaidUnit_Sum
#// Same rule through the REAL dispatch path: SOR_154 Rallying Cry is PLAYED (cost 3, Aggression x2 —
#// covered by the rrk Aggression base + Aggression leader, so it plays at printed cost) and gives each
#// friendly unit in play Raid 2 for the phase. Tarpals (printed Raid 2, 0 power) then attacks for 4.
#// Old max() = 2.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:3
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_154
WithP1GroundArena: HMW_254:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:4
P2BASEDMG:4

---

# TwoDifferentPhaseGrants_Sum
#// Two phase grants from two different sources on a unit with NO printed Raid: SOR_154 Rallying Cry
#// (Raid 2) + LOF_152 Focus Determines Reality (Raid 1). SWUTurnEffectKeywordValue took the HIGHEST
#// instance; summed = 3, so the 3-power Battlefield Marine deals 6. Old = 5.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0:SOR_154~LOF_152

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:3
P2BASEDMG:6

---

# TwoInvasionControlShips_EachGrantsItsOwnRaid2
#// LOF_169 Invasion Control Ship — "Friendly Droid units gain Raid 2." NON-unique, so two ships are two
#// sources: Raid 4. It was an "is one in play?" boolean (Raid 2). Imperial Dark Trooper is a 3/3 Droid,
#// so it deals 3 + 4 = 7 (old 5).

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LOF_169:1:0
WithP1SpaceArena: LOF_169:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:4
P2BASEDMG:7

---

# ABlankedInvasionControlShipGrantsNothing
#// LOF_169 — the count is over ACTIVE ships. A ship carrying SOR_138 Force Lightning's lose-abilities
#// marker grants nothing, the rule the friendly-unit grant loop already applied to a blanked granter.
#// (The old read, _SWUCountUnitsWithCardID, did not filter blanked units, so this ship still granted.)
#// One ship, blanked → the Droid has no Raid and deals its printed 3.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LOF_169:1:0:SOR_138
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P2BASEDMG:3

---

# TwoCloneCohortsOnOneUnit_Raid4
#// TWI_169 Clone Cohort — "Attached unit gains Raid 2." NON-unique, so two on one unit are two sources:
#// Raid 4. It was a boolean "has one attached?" (Raid 2). Clone Cohort is +0/+0 in the dictionary, so the
#// Battlefield Marine deals 3 + 4 = 7 (old 5).

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_169
WithP1GroundArenaUpgrade: 0:TWI_169

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:4
P2BASEDMG:7

---

# MarchionRoDoublesTheSummedTotal
#// ⚠ LOF_186 Marchion Ro — "Each friendly unit's Raid is doubled." His handler RE-DERIVES the generated
#// base value to double it, so it has to change in lockstep with the generator. Tarpals: printed Raid 2
#// + Rallying Cry Raid 2 = 4, doubled = 8 (and he has 0 power, so the damage is exactly 8).
#//   • old engine (max everywhere):                       2 → doubled 4
#//   • generator summed but Marchion still using max():   4 + 2 = 6
#//   • both summed:                                       8
#// Only the last is right, and only this section can tell the middle one from it.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_254:1:0:SOR_154
WithP1GroundArena: LOF_186:0:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:8
P2BASEDMG:8

---

# TwinSuns_TwoEnemyGalensGrantRaid2
#// LAW_233 Galen Erso — "Enemy units gain Raid 1 and Saboteur." Unique, so one player can control only
#// one — but in Twin Suns TWO OPPONENTS can each control one, and each is its own source: Raid 2. It used
#// to be a deliberate boolean ("two enemy Galens still grant Raid 1"). Saboteur does NOT stack, so it is
#// simply present.
#// Cannot pass at two seats: with one opponent there is only one Galen.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_233:1:0
WithP3GroundArena: LAW_233:1:0

## WHEN

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
