# BeastAndOffer_EnemyUnitsOnly
#// COVERAGE: offer=this section · decline=N/A (STRUCTURAL: mandatory)
#//           boundary=N/A (STRUCTURAL: fixed amount) · independence=NoEnemyUnit_BeastStillCreated
#//           control=N/A · reqboundary=AcrossTheRequestBoundary
#//           modes=2P,TeamSuns (text says "an enemy unit") — TeamSuns_TeammateIsNotAnEnemy
#//
#// HMW_250 Imperial Cavalry — Unit (Ground) 4/4, cost 6, [Villainy], Imperial/Trooper.
#// "When Played: Create a Beast token and deal 1 damage to an enemy unit."

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_250
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:HMW_T03
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# DealsOneToTheChosenEnemy

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_250
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoEnemyUnit_BeastStillCreated

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_250

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1NODECISION

---

# SingleEnemy_AutoResolves

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_250
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1NODECISION

---

# TeamSuns_TeammateIsNotAnEnemy

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_250
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P3GROUNDARENAUNIT:0:DAMAGE:0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_250
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
