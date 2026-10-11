# DealsDamageEqualToItsRaid
#// COVERAGE: offer=Offer_FriendlyUnits and Offer_EnemyUnits · decline=N/A (STRUCTURAL: mandatory)
#//           zero=NoRaid_DealsNothing · quantity=StackedRaidCounts (Raid is summed, CR 7.5.8.b)
#//           no-target=NoEnemyUnit_NoPrompt · control=N/A · reqboundary=AcrossTheRequestBoundary
#//           modes=2P,TeamSuns (text says "friendly"/"enemy") — TeamSuns_TeammatesUnitCanFire
#//
#// HMW_192 Volley Fire — Event, cost 1, [Aggression], Tactic.
#// "A friendly unit deals damage equal to its Raid to an enemy unit."
#// SOR_095 with HMW_190 Enraged = Raid 2; SEC_080 has no Raid.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_192
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP1GroundArenaUpgrade: 0:HMW_190
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Offer_FriendlyUnits

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_192
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Offer_EnemyUnits

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_192
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# NoRaid_DealsNothing

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_192
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# StackedRaidCounts
#// Two Enraged on one unit = Raid 4.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_192
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: [0:HMW_190 0:HMW_190]
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# NoEnemyUnit_NoPrompt

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_192
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:HMW_190

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2BASEDMG:0

---

# TeamSuns_TeammatesUnitCanFire

## GIVEN
CommonSetup: rrk/yyw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_192
WithP1GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_095:1:0
WithP3GroundArenaUpgrade: 0:HMW_190
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_192
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP1GroundArenaUpgrade: 0:HMW_190
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:2
