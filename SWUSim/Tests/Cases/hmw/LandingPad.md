# OnYourBase_FriendlySpaceUnitsPlusOne
#// COVERAGE: offer=N/A (STRUCTURAL: a base-granted constant ability; Fortify's attach is generic)
#//           decline=N/A (STRUCTURAL: no "may") · boundary=N/A (STRUCTURAL: a fixed value)
#//           quantity=TwoCopiesStack · negative=EnemySpaceUnits_NotBuffed and this section's ground unit
#//           control=N/A (the grant reads the unit's controller; bases never change hands)
#//           reqboundary=N/A (STRUCTURAL: live read) · dispatch=PlayedFromHand_AttachesToYourBase
#//           modes=2P,TeamSuns,TwinSuns (text says "Friendly") — TeamSuns_TeammatesPadBuffsYou and
#//           TwinSuns_OpponentsPadDoesNot
#//
#// HMW_271 Landing Pad — Upgrade, cost 3, no aspects, Fortification.
#// "Fortify. Attached base gains: 'Friendly space units get +1/+0.'"

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_271
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:POWER:3

---

# EnemySpaceUnits_NotBuffed

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_271
WithP2SpaceArena: SOR_225:1:0

## EXPECT
P2SPACEARENAUNIT:0:POWER:2

---

# TwoCopiesStack

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: [HMW_271 HMW_271]
WithP1SpaceArena: SOR_237:1:0

## EXPECT
P1SPACEARENAUNIT:0:POWER:4

---

# PlayedFromHand_AttachesToYourBase

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_271
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:3

---

# TheBonusDealsDamage

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_271
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# TeamSuns_TeammatesPadBuffsYou

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3BaseUpgrade: HMW_271
WithP1SpaceArena: SOR_237:1:0

## EXPECT
P1SPACEARENAUNIT:0:POWER:3

---

# TwinSuns_OpponentsPadDoesNot

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP3BaseUpgrade: HMW_271
WithP1SpaceArena: SOR_237:1:0

## EXPECT
SEATCOUNT:4
P1SPACEARENAUNIT:0:POWER:2
