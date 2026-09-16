# HealsYourBaseAndTheChosenFriendlyUnit
#// COVERAGE: offer=Offer_FriendlyUnitsOnly_ItselfIncluded · decline=N/A (STRUCTURAL: mandatory)
#//           boundary=HealsClampAtZero (1 damage → 0, never negative)
#//           control=N/A (no owner-scoped zone) · reqboundary=AcrossTheRequestBoundary
#//           no-target=N/A (STRUCTURAL: the Frigate is itself a friendly unit; its base always exists)
#//           modes=2P,TeamSuns (text says "a friendly base" / "a friendly unit") — TeamSuns_TeammatesBaseIsOffered
#//
#// HMW_091 Pelta Relief Frigate — Unit (Space) 5/4, cost 5, [Vigilance], Republic/Vehicle/Transport.
#// "When Played: Heal 2 damage from a friendly base and 2 damage from a friendly unit."
#// In 2P the friendly base is P1's own and resolves without a prompt.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_091
WithP1GroundArena: [SOR_046:1:4 SOR_095:1:2]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# Offer_FriendlyUnitsOnly_ItselfIncluded

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_091
WithP1GroundArena: SOR_046:1:4
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:3
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# HealsClampAtZero

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;myBaseDamage:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_091
WithP1GroundArena: SOR_046:1:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# OnlyItself_AutoResolves

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_091

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:3
P1SPACEARENACOUNT:1
P1NODECISION

---

# TeamSuns_TeammatesBaseIsOffered

## GIVEN
CommonSetup: bbk/yyw/{myResources:5;myBaseDamage:4}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:6
WithP4Base: SOR_019:0
WithP1Hand: HMW_091

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myBase-0&p3Base-0

---

# TeamSuns_HealsTheTeammatesBase

## GIVEN
CommonSetup: bbk/yyw/{myResources:5;myBaseDamage:4}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:6
WithP4Base: SOR_019:0
WithP1Hand: HMW_091

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3Base-0

## EXPECT
P1BASEDMG:4
P3BASEDMG:4

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_091
WithP1GroundArena: [SOR_046:1:4 SOR_095:1:2]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1BASEDMG:3
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:4
