# SharesATraitWithAnotherFriendlyUnit_PlusTwo
#// COVERAGE: offer=ExhaustedUnitsAreNotOffered · decline=N/A (STRUCTURAL: mandatory)
#//           boundary=N/A (STRUCTURAL: a trait match) · negative=NoSharedTrait_NoBonus and
#//           AloneOnTheBoard_NoBonus ("another") and EnemySharingDoesNotCount
#//           duration=BonusIsForThisAttackOnly · control=N/A · reqboundary=N/A (STRUCTURAL: a lone ready
#//           unit auto-resolves; a multi-unit pick is covered by NoSharedTrait_NoBonus)
#//           modes=2P,TeamSuns (text says "another friendly unit") — TeamSuns_TeammatesUnitCounts
#//
#// HMW_266 Familiar Strategem — Event, cost 1, [Heroism], Tactic.
#// "Attack with a unit. If it shares a Trait with another friendly unit, it gets +2/+0 for this attack."
#// SOR_095 and SOR_046 are both Rebel/Trooper.

## GIVEN
CommonSetup: yyw/yyw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_266
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5

---

# NoSharedTrait_NoBonus
#// SOR_225 TIE (Imperial/Vehicle/Fighter) shares nothing with SOR_095.

## GIVEN
CommonSetup: yyw/yyw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_266
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3

---

# AloneOnTheBoard_NoBonus

## GIVEN
CommonSetup: yyw/yyw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_266
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3

---

# EnemySharingDoesNotCount
#// P2's SOR_046 is Rebel/Trooper too, but not friendly. SOR_095 attacks P2's base.

## GIVEN
CommonSetup: yyw/yyw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_266
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:3

---

# ExhaustedUnitsAreNotOffered

## GIVEN
CommonSetup: yyw/yyw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_266
WithP1GroundArena: [SOR_095:0:0 SOR_046:1:0 SEC_080:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-2

---

# BonusIsForThisAttackOnly

## GIVEN
CommonSetup: yyw/yyw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_266
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3

---

# TeamSuns_TeammatesUnitCounts

## GIVEN
CommonSetup: yyw/bbk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_266
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2Base-0

## EXPECT
P2BASEDMG:5
