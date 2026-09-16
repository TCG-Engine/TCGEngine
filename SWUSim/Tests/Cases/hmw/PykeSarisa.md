# Offer_AnyUnitEitherSide_SelfIncluded
#// COVERAGE: offer=this section · decline=N/A (STRUCTURAL: mandatory — no "you may")
#//           boundary=N/A (STRUCTURAL: no number or threshold) · duration=ExpiresAtTheEndOfThePhase
#//           control=N/A (no owner-scoped zone or "your" wording) · reqboundary=AcrossTheRequestBoundary
#//           no-target=N/A (STRUCTURAL: the Sarisa is itself "a unit"; OnlyItself_AutoResolves covers the
#//           single-target path)
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_246 Pyke Sarisa — Unit (Space) 4/3, cost 4, [Villainy], Underworld/Vehicle/Transport.
#// "When Played: Give a unit Sentinel for this phase."

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_246
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0

---

# GivesAFriendlyUnitSentinel

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_246
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# CanGiveAnEnemyUnitSentinel

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_246
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# OnlyItself_AutoResolves
#// The Sarisa is the only unit: a mandatory single-target choose resolves onto it with no prompt.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_246

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1NODECISION

---

# ExpiresAtTheEndOfThePhase

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_246
WithP1GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_246
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
