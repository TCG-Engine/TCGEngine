# BeastThenOneToAFriendlyAndOneToAnEnemyGroundUnit
#// COVERAGE: offer=Offer_FriendlyGroundOnly and Offer_EnemyGroundOnly · decline=N/A (STRUCTURAL: mandatory)
#//           boundary=N/A (STRUCTURAL: fixed amounts) · independence=NoEnemyGroundUnit_FirstHalfStillResolves
#//           no-target=N/A for the friendly half (STRUCTURAL: the new Beast is always a friendly ground unit)
#//           control=N/A (no owner-scoped zone) · reqboundary=AcrossTheRequestBoundary
#//           modes=2P,TeamSuns (text says "friendly"/"enemy") — TeamSuns_TeammateIsFriendlyNotEnemy
#//
#// HMW_194 Run Amok — Event, cost 3, [Aggression], Disaster.
#// "Create a Beast token. Deal 1 damage to a friendly ground unit and 1 damage to an enemy ground unit."
#// The Beast lands at P1 ground 1 (after the seeded SOR_046).

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_194
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Offer_FriendlyGroundOnly
#// P1's space X-Wing and P2's ground units are out.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_194
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Offer_EnemyGroundOnly
#// P2's space TIE is out.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_194
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_128:1:0]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# OnlyTheBeast_BothAutoResolve

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_194
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION

---

# NoEnemyGroundUnit_FirstHalfStillResolves

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_194
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# TeamSuns_TeammateIsFriendlyNotEnemy

## GIVEN
CommonSetup: rrk/yyw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_194
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# TeamSuns_EnemyDamageSkipsTheTeammate

## GIVEN
CommonSetup: rrk/yyw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_194
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
P3GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_194
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:1
