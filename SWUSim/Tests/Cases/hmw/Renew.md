# Offer_ConditionUpgradesOnly
#// COVERAGE: offer=this section · decline=Decline_StillHeals
#//           boundary=HealClampsAtZero · independence=NoConditionUpgrade_NoPrompt_StillHeals
#//           control=N/A (a defeated upgrade goes to its owner's discard; "your base" is the caster's)
#//           reqboundary=AcrossTheRequestBoundary · modes=2P only (no player reference; "your base" self-only)
#//
#// HMW_267 Renew — Event, cost 2, [Heroism], Innate.
#// "You may defeat a Condition upgrade. Heal 3 damage from your base."
#// HMW_T02 Weakness is a Condition; SOR_120 Academy Training is Learned (out).

## GIVEN
CommonSetup: yyw/yyw/{myResources:2;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_267
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: [0:HMW_T02 0:SOR_120]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0.u0&theirGroundArena-0.u0

---

# DefeatsAWeakness_ThenHeals

## GIVEN
CommonSetup: yyw/yyw/{myResources:2;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_267
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0.u0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1BASEDMG:2

---

# Decline_StillHeals

## GIVEN
CommonSetup: yyw/yyw/{myResources:2;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_267
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1BASEDMG:2
P1NODECISION

---

# NoConditionUpgrade_NoPrompt_StillHeals

## GIVEN
CommonSetup: yyw/yyw/{myResources:2;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_267
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1BASEDMG:2
P1NODECISION

---

# HealClampsAtZero

## GIVEN
CommonSetup: yyw/yyw/{myResources:2;myBaseDamage:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_267

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyw/yyw/{myResources:2;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_267
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1BASEDMG:2
