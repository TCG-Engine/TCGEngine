# Offer_AnyUnitEitherSide_ReadyOrExhausted_SelfIncluded
#// COVERAGE: offer=this section · decline=Decline_TargetStaysReady
#//           boundary=N/A (STRUCTURAL: no number or threshold in the text — "a unit" with no qualifier)
#//           control=N/A (no owner-scoped zone or "your" wording) · reqboundary=AcrossTheRequestBoundary
#//           no-target=N/A (STRUCTURAL: the Purrgil is itself "a unit" while its When Played resolves)
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//           Restore 2 = keyword-only half, auto-wired by the generator (Step-0 no-op, keyword tested generically)
#//
#// HMW_092 Starlit Purrgil — Unit (Space) 4/5, cost 6, [Vigilance], Creature.
#// "Restore 2. When Played: You may exhaust a unit."
#// SEC_189 Lurking Snub Fighter's text. An already-exhausted unit is a legal (no-op) pick.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_092
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# ExhaustsAnEnemyUnit

## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_092
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:READY

---

# ExhaustsAFriendlyUnit

## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_092
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:READY

---

# Decline_TargetStaysReady

## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_092
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_092
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
