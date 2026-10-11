# Offer_NonLeaderUnitsIncludingTheBeast
#// COVERAGE: offer=this section · decline=Decline_NothingReturned
#//           boundary=N/A (STRUCTURAL: no number) · token=TheNewBeastCeases
#//           control=N/A (BOUNCE_UNIT returns to the OWNER's hand) · reqboundary=AcrossTheRequestBoundary
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_241 Howl — Event, cost 6, [Cunning], Innate.
#// "Create a Beast token. You may return a non-leader unit to its owner's hand."

## GIVEN
CommonSetup: yyk/yyk/{myResources:6;theirLeader:SOR_014:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_241
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# ReturnsAnEnemyUnit

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_241
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1GROUNDARENACOUNT:1

---

# Decline_NothingReturned

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_241
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENACOUNT:1
P1NODECISION

---

# TheNewBeastCeases

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_241

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_241
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2HANDCOUNT:1
