# Offer_ThreePowerInFourOut_EnemyIncluded_SelfExcluded
#// COVERAGE: offer=this section · decline=Decline_UnitStaysExhausted
#//           boundary=this section (SOR_095 3 in / LAW_124 4 out)
#//           control=N/A (no owner-scoped zone or "your" wording)
#//           reqboundary=AcrossTheRequestBoundary · no-target=OnlyItself_NoPrompt ("another")
#//           modes=2P only (no player reference; "another unit" has no friendly/enemy wording)
#//
#// HMW_165 Commandeered Tour Shuttle — Unit (Space) 2/2, cost 3, [Aggression][Heroism], Rebel/Vehicle/Transport.
#// "When Played: You may ready another unit with 3 or less power."
#// The Shuttle (2 power) would qualify without "another". "a unit" is unqualified, so an enemy is legal.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_165
WithP1GroundArena: [SOR_095:0:0 LAW_124:0:0]
WithP2SpaceArena: SOR_225:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SELECTABLEEXACT:myGroundArena-0&theirSpaceArena-0

---

# ReadiesAFriendlyUnit

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_165
WithP1GroundArena: [SOR_095:0:0 LAW_124:0:0]
WithP2SpaceArena: SOR_225:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:EXHAUSTED
P2SPACEARENAUNIT:0:EXHAUSTED

---

# ReadiesAnEnemyUnit

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_165
WithP1GroundArena: SOR_095:0:0
WithP2SpaceArena: SOR_225:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Decline_UnitStaysExhausted

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_165
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# OnlyItself_NoPrompt
#// The Shuttle is the only unit in play and is 2 power — "another" is what keeps it out.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_165

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_165
WithP1GroundArena: SOR_095:0:0
WithP2SpaceArena: SOR_225:0:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P2SPACEARENAUNIT:0:EXHAUSTED
