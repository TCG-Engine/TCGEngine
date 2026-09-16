# ThreeUnits_GetsPlusTwo
#// COVERAGE: offer=N/A (STRUCTURAL: a constant ability, nothing is chosen) · decline=N/A (STRUCTURAL: no "may")
#//           boundary=this section (3) paired with TwoUnits_NoBonus (2)
#//           control=N/A (no owner-scoped zone; "you control" reads the unit's CONTROLLER)
#//           reqboundary=N/A (STRUCTURAL: recomputed on every read, no state is written)
#//           duration=EndsWhenAUnitLeaves · modes=2P only ("you control" is self-only in every format) —
#//           TeamSuns_TeammatesUnitsDoNotCount pins that
#//
#// HMW_129 Child of Dathomir — Unit (Ground) 1/2, cost 1, [Command], Night.
#// "While you control 3 or more units (including this one), this unit gets +2/+0."

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_129:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_237:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:2

---

# TwoUnits_NoBonus

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_129:1:0 SOR_095:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0 SOR_046:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1

---

# TokensAndLeaderUnitsCount
#// A Battle Droid token and a deployed Sabine Wren leader unit are both units you control.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:SOR_014:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_129:1:0 TWI_T01:1:0]

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:POWER:3

---

# EndsWhenAUnitLeaves
#// SOR_128 Death Star Stormtrooper (3/1) attacks SOR_046 (3/7) and dies — back to two units, back to 1 power.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_129:1:0 SOR_095:1:0 SOR_128:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:2:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:POWER:1

---

# TheBonusDealsDamage

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_129:1:0 SOR_095:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# TeamSuns_TeammatesUnitsDoNotCount
#// "you control" — seat 3's two units are friendly but not yours.

## GIVEN
CommonSetup: ggw/bbk
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: [HMW_129:1:0 SOR_095:1:0]
WithP3GroundArena: [SOR_095:1:0 SOR_095:1:0]

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1
