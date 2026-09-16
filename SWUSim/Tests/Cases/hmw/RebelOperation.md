# RebelLeaderOnly_CostsThree
#// COVERAGE: offer=N/A (STRUCTURAL: a cost modifier + a draw) · decline=N/A (STRUCTURAL: mandatory)
#//           boundary=OneRebelUnit_ThreeResourcesIsEnough vs OneRebelUnit_OneResourceIsNot
#//           negative=NonRebelUnitDoesNotCount and EnemyRebelsDoNotCount · count-once=DeployedRebelLeaderCountsOnce
#//           control=N/A · reqboundary=N/A (STRUCTURAL: computed at play time, no decision)
#//           modes=2P,TeamSuns (text says "friendly") — TeamSuns_TeammatesRebelCounts
#//
#// HMW_173 Rebel Operation — Event, cost 4, [Aggression][Heroism], Rebel/Plan.
#// "This card costs 1 resource less to play for each friendly Rebel unit and leader. Draw 2 cards."
#// CommonSetup rrw = SOR_014 Sabine Wren (Mandalorian/Rebel/Spectre) — a friendly Rebel leader: cost 3.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_173
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:1
P1RESAVAILABLE:0
P1DISCARDCOUNT:1

---

# RebelLeaderOnly_TwoResourcesIsNotEnough

## GIVEN
CommonSetup: rrw/rrw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_173
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:2

---

# TwoRebelUnits_CostsOne

## GIVEN
CommonSetup: rrw/rrw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_173
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:2
P1RESAVAILABLE:0

---

# NonRebelUnitDoesNotCount

## GIVEN
CommonSetup: rrw/rrw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_173
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:2

---

# EnemyRebelsDoNotCount

## GIVEN
CommonSetup: rrw/rrw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_173
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:2

---

# DeployedRebelLeaderCountsOnce
#// Sabine deployed is a Rebel UNIT and a leader — one Rebel, not two: cost 3, so 2 resources is not enough.

## GIVEN
CommonSetup: rrw/rrw/{myResources:2;myLeader:SOR_014:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_173
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1RESAVAILABLE:2

---

# TeamSuns_TeammatesRebelCounts

## GIVEN
CommonSetup: rrw/bbk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_173
WithP3GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:2
P1RESAVAILABLE:0
