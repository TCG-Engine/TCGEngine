# EachOtherFriendlyUnitGetsPlusTwo
#// COVERAGE: offer=N/A (STRUCTURAL: "each" — nothing is chosen) · decline=N/A (STRUCTURAL: mandatory)
#//           boundary=N/A (STRUCTURAL: no threshold) · quantity=TwoLandersStack
#//           negative=this section (the Lander itself and the enemy unit stay printed)
#//           duration=ExpiresAtTheEndOfThePhase · snapshot=UnitsPlayedLaterGetNothing
#//           control=N/A (no owner-scoped zone) · reqboundary=N/A (no decision; the buff is a registered
#//           phase token that survives the request round-trip like every STAT_BUFF)
#//           modes=2P,TeamSuns,TwinSuns (text says "friendly") — TeamSuns_TeammatesUnitIsBuffed and
#//           TwinSuns_OpponentAtSeatThreeIsNot
#//
#// HMW_111 Invasion Lander — Unit (Space) 3/7, cost 6, [Command][Villainy], Separatist/Vehicle/Transport.
#// "When Played: Give each other friendly unit +2/+2 for this phase."

## GIVEN
CommonSetup: ggk/ggk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_111
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:1:CARDID:HMW_111
P1SPACEARENAUNIT:1:POWER:3
P2GROUNDARENAUNIT:0:POWER:3

---

# ExpiresAtTheEndOfThePhase

## GIVEN
CommonSetup: ggk/ggk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_111
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# UnitsPlayedLaterGetNothing

## GIVEN
CommonSetup: ggk/ggk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_111 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:3

---

# TwoLandersStack

## GIVEN
CommonSetup: ggk/ggk/{myResources:12}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_111 HMW_111]
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:7
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:1:POWER:3

---

# TeamSuns_TeammatesUnitIsBuffed

## GIVEN
CommonSetup: ggk/yyw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_111
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P3GROUNDARENAUNIT:0:POWER:5
P4GROUNDARENAUNIT:0:POWER:3

---

# TwinSuns_OpponentAtSeatThreeIsNot

## GIVEN
CommonSetup: ggk/yyw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_111
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:POWER:3
