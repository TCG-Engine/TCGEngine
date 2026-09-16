# VillainyHost_OffersAnyUnit
#// COVERAGE: offer=this section · decline=Decline_NoDamage
#//           boundary=N/A (STRUCTURAL: an aspect test, not a number) · gate negatives=HeroismHost_NoPrompt and
#//           VigilanceOnlyHost_NoPrompt (the gate is "Villainy", not "not Heroism")
#//           control=N/A (no owner-scoped zone or "your" wording) · reqboundary=AcrossTheRequestBoundary
#//           no-target=N/A (STRUCTURAL: the Villainy host is itself "a unit")
#//           dispatch=EnemyVillainyHost_StillOffers (the host's aspect, whoever controls it)
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_252 Villainous Ambition — Upgrade +2/+0, cost 2, [Villainy], Innate.
#// "When Played: If attached unit is a Villainy unit, you may deal 2 damage to a unit."
#// SEC_080 Imperial Dark Trooper is [Command][Villainy]. Attach is to "a unit", so with enemies present
#// the host is picked first.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_252
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_unit
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirGroundArena-1

---

# DealsTwoToTheChosenUnit

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_252
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:5

---

# HeroismHost_NoPrompt

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_252
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# VigilanceOnlyHost_NoPrompt
#// SOR_063 Cloud City Wing Guard is [Vigilance] only — neither Heroism nor Villainy.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_252
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# EnemyVillainyHost_StillOffers
#// No friendly unit: the upgrade auto-attaches to P2's lone SEC_080 — a Villainy unit — and the offer opens.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_252
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Decline_NoDamage

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_252
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_252
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
