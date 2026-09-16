# WhenPlayed_OpponentChoosesAUnit_YouDealTwo
#// COVERAGE: offer=Offer_TheOpponentsOwnUnitsAndBase · decline=CasterDeclines_NoDamage
#//           boundary=N/A (STRUCTURAL: fixed amount) · no-unit=OpponentHasNoUnits_OnlyTheBaseIsLeft
#//           path=OnAttack_AlsoTriggers · control=N/A (the opponent picks among what they CONTROL)
#//           reqboundary=AcrossTheRequestBoundary
#//           modes=2P,TwinSuns (text says "an opponent") — TwinSuns_TheChosenSeatPicks
#//
#// HMW_244 Separatist Harbinger — Unit (Space) 1/4, cost 3, [Villainy], Separatist/Vehicle/Transport.
#// "When Played/On Attack: An opponent chooses a unit or base they control. You may deal 2 damage to it."
#// With one opponent the opponent pick resolves silently; P2 then chooses among its own board.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_244
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:0

---

# Offer_TheOpponentsOwnUnitsAndBase

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_244
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&myBase-0

---

# OpponentChoosesItsBase

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_244
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myBase-0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# CasterDeclines_NoDamage

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_244
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# OpponentHasNoUnits_OnlyTheBaseIsLeft

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_244

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:2

---

# OnAttack_AlsoTriggers
#// The Harbinger (1 power) attacks the base; P2 offers its unit; 2 damage to it, 1 to the base from combat.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_244:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:1

---

# TwinSuns_TheChosenSeatPicks

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1Hand: HMW_244
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3
- P3>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_244
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P2>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
