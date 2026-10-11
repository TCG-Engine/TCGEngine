# Offer_OneRemainingInTwoRemainingOut_LeaderExcluded
#// COVERAGE: offer=this section · decline=Decline_NothingIsDefeated
#//           boundary=this section (1 remaining in / 2 remaining out) and WeaknessShrinkCounts (a -1/-1
#//           token, not damage, brings a unit to 1)
#//           control=N/A (no owner-scoped zone or "your" wording; DEFEAT_UNIT discards to the owner)
#//           reqboundary=AcrossTheRequestBoundary · no-target=NoLegalTarget_NoPrompt
#//           modes=2P only (no player reference; "a non-leader unit" has no friendly/enemy wording)
#//
#// HMW_086 N-1 Patroller — Unit (Space) 2/2, cost 3, [Vigilance], Naboo/Vehicle/Fighter.
#// "When Played: You may defeat a non-leader unit with 1 or less remaining HP."
#//
#// P2 ground: SOR_095 3/3 with 2 damage (1 remaining, in) · SOR_046 3/7 with 5 damage (2 remaining, out)
#// · SOR_128 3/1 undamaged (1 remaining, in) · deployed SOR_014 Sabine 2/5 with 4 damage (1 remaining,
#// out — a leader). The Patroller lands undamaged at 2 and is not offered.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3;theirLeader:SOR_014:1:1:0:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_086
WithP2GroundArena: [SOR_095:1:2 SOR_046:1:5 SOR_128:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENACOUNT:4
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-2

---

# DefeatsTheChosenUnit

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_086
WithP2GroundArena: [SOR_095:1:2 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2DISCARDCOUNT:1

---

# Decline_NothingIsDefeated

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_086
WithP2GroundArena: [SOR_095:1:2 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:2
P2DISCARDCOUNT:0
P1NODECISION

---

# NoLegalTarget_NoPrompt
#// Only a 3/7 with no damage in play — nothing at 1 or less. A fizzle-only optional is never offered.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_086
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1NODECISION
P2GROUNDARENACOUNT:1

---

# WeaknessShrinkCounts
#// SOR_207 Crafty Smuggler 2/2 with a Weakness token (-1/-1) is 1/1 with no damage: remaining HP 1, in.
#// A read of printed HP minus damage leaves it at 2 and out. SOR_046 3/7 stays out.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_086
WithP2GroundArena: [SOR_046:1:0 SOR_207:1:0 SOR_128:1:0]
WithP2GroundArenaUpgrade: 1:HMW_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-1&theirGroundArena-2

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_086
WithP2GroundArena: [SOR_095:1:2 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2DISCARDCOUNT:1
