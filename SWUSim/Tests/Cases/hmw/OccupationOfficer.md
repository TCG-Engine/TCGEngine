# SixResources_OffersAnyUnit
#// COVERAGE: offer=this section · decline=Decline_NoToken
#//           boundary=this section (6) paired with FiveResources_NoPrompt (5)
#//           control=N/A (no owner-scoped zone or "your" wording; "you control" resources are the caster's)
#//           reqboundary=AcrossTheRequestBoundary
#//           no-target=N/A (STRUCTURAL: the Officer is itself "a unit" while its When Played resolves)
#//           modes=2P only ("you control" is self-only in every format; no friendly/enemy wording)
#//
#// HMW_242 Occupation Officer — Unit (Ground) 3/2, cost 2, [Villainy], Imperial.
#// "When Played: If you control 6 or more resources, you may give a Weakness token to a unit."
#// Paying 2 exhausts two of the six — "control" still counts them.

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_242
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:4
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# GivesWeaknessToAnEnemyUnit

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_242
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# FiveResources_NoPrompt
#// The boundary partner: one short of the threshold, nothing is offered.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_242
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# ExhaustedResourcesCount
#// 6 resources, 4 of them already exhausted before the play: they are still resources P1 controls.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_242
WithP1Resources: 2:SOR_046:1,4:SOR_046:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Decline_NoToken

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_242
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_242
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
