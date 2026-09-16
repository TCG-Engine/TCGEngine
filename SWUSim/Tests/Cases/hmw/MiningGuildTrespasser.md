# Yes_DealsTwoToABaseAndTwoToAnEnemyUnit
#// COVERAGE: offer=Offer_EnemyUnitsOnly (the base pool is SWUOfferBaseTarget's; asserted in Offer_AnyBase)
#//           decline=No_NothingHappens · boundary=N/A (STRUCTURAL: fixed amounts)
#//           independence=NoEnemyUnit_BaseStillDamaged · control=N/A (no owner-scoped zone)
#//           reqboundary=AcrossTheRequestBoundary
#//           modes=2P,TeamSuns (text says "an enemy unit") — TeamSuns_TeammateIsNotAnEnemy
#//
#// HMW_186 Mining Guild Trespasser — Unit (Space) 5/5, cost 6, [Aggression], Vehicle/Transport.
#// "When Played: You may deal 2 damage to a base and 2 damage to an enemy unit."

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_186
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# Offer_AnyBase

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_186
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# Offer_EnemyUnitsOnly

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_186
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# No_NothingHappens

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_186
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:0
P1BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# NoEnemyUnit_BaseStillDamaged

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_186

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1NODECISION

---

# CanDamageYourOwnBase

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_186
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# TeamSuns_TeammateIsNotAnEnemy
#// Seat 3 is P1's teammate; P2 is the only enemy with a unit, so the unit damage resolves onto P2's.

## GIVEN
CommonSetup: rrk/yyw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_186
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:p2Base-0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P3GROUNDARENAUNIT:0:DAMAGE:0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_186
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:1:DAMAGE:2
