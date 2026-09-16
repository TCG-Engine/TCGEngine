# Offer_FriendlyUnitsIncludingTheBeastAndItself
#// COVERAGE: offer=this section · decline=N/A (STRUCTURAL: mandatory)
#//           boundary=N/A (STRUCTURAL: no number) · no-target=N/A (STRUCTURAL: the Beast is always friendly)
#//           control=N/A (no owner-scoped zone) · reqboundary=AcrossTheRequestBoundary
#//           order=this section (the Beast is offered, so the pool is built AFTER it is created)
#//           modes=2P,TeamSuns (text says "a friendly unit") — TeamSuns_TeammatesUnitIsOffered
#//
#// HMW_199 Geonosian Picador — Unit (Ground) 2/2, cost 3, [Cunning][Villainy], Separatist.
#// "When Played: Create a Beast token. Then, give a Weakness token to a friendly unit."
#// Picador at P1 ground 0, the Beast at 1; P2's SOR_095 is not friendly.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_199
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# WeaknessOnTheBeast

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_199
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# WeaknessCanDefeatADamagedFriendly
#// SOR_095 3/3 with 2 damage → 2/2 with 2 damage, defeated.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_199
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1DISCARDCOUNT:1

---

# TeamSuns_TeammatesUnitIsOffered

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_199
WithP3GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&p3GroundArena-0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_199

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:HP:1
