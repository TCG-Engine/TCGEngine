# WhenPlayed_Offer_ThreePowerOrLessEitherSide
#// COVERAGE: offer=this section and OnAttack_Offer_AnotherUnit · decline=WhenPlayed_Decline and OnAttack_Decline
#//           boundary=this section (SOR_095 3 in / LAW_124 4 out) · another=OnAttack_Offer_AnotherUnit (Ben out)
#//           clamp=OnAttack_HealClampsAtZero · control=N/A · reqboundary=AcrossTheRequestBoundary
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_261 Ben Kenobi — Unit (Ground) 5/5, cost 5, [Heroism], Force/Fringe/Jedi.
#// "When Played: You may exhaust a unit with 3 or less power. On Attack: You may heal 3 damage from another unit."

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_261
WithP1GroundArena: [SOR_095:1:0 LAW_124:1:0]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# WhenPlayed_ExhaustsAnEnemy

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_261
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:READY

---

# WhenPlayed_Decline

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_261
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# OnAttack_Offer_AnotherUnit
#// Ben (damaged) attacks the base; the pool is every damaged-or-not unit except Ben.

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_261:1:2 SOR_046:1:5]
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&theirGroundArena-0

---

# OnAttack_HealsThree

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_261:1:2 SOR_046:1:5]
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:5

---

# OnAttack_HealClampsAtZero

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_261:1:0 SOR_046:1:1]
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# OnAttack_Decline

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_261:1:0 SOR_046:1:5]
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:5
P2BASEDMG:5

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyw/yyw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_261:1:0 SOR_046:1:5]
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
