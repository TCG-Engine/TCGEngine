# DefeatOne_ExhaustTwo
#// COVERAGE: offer=Offer_FriendlyUnits (defeat pool) and Offer_EnemyUnits (exhaust pool)
#//           decline=DefeatNone_NothingHappens ("any number" includes zero)
#//           quantity=this section (1 → 2) and DefeatTwo_EnemyCountCaps (2 → 4, capped at 3 → all)
#//           cap=OverAnsweredExhaust_OnlyTheOfferedNumber · no-target=NoEnemyUnits_DefeatStillHappens
#//           control=N/A · reqboundary=AcrossTheRequestBoundary
#//           modes=2P,TeamSuns (text says "friendly"/"enemy") — TeamSuns_TeammatesUnitCanBeDefeated
#//
#// HMW_253 Forced Pacification — Event, cost 2, [Villainy], Plan.
#// "Defeat any number of friendly units. For each friendly unit defeated this way, exhaust 2 enemy units."

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_253
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-2

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:2:EXHAUSTED

---

# Offer_FriendlyUnits

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_253
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Offer_EnemyUnits

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_253
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

---

# DefeatNone_NothingHappens

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_253
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:READY
P1NODECISION

---

# DefeatTwo_EnemyCountCaps
#// Two defeated → exhaust 4, but only 3 enemy units: all three exhaust with no further prompt.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_253
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:3
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:2:EXHAUSTED
P1NODECISION

---

# NoEnemyUnits_DefeatStillHappens

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_253
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1NODECISION

---

# TeamSuns_TeammatesUnitCanBeDefeated

## GIVEN
CommonSetup: yyk/bbw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_253
WithP1GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_253
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:2:EXHAUSTED

---

# OverAnsweredExhaust_OnlyTheOfferedNumber
#// One defeat offers exactly 2. The resolver clamps to the count carried in its own Param, so a third mzID
#// in the answer is ignored — the offer and the resolution cannot disagree.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_253
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:2:READY
