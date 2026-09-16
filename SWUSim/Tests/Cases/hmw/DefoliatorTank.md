# PayTwo_TwoWeaknessOnTheDefender
#// COVERAGE: offer=N/A (STRUCTURAL: a YESNO on the defender) · decline=Decline_NoTokens
#//           cannot-pay=OneResource_NoOffer · negative=DroidDefender_NoOffer and VehicleDefender_NoOffer
#//           base=BaseAttack_NoOffer · interaction=WeaknessBeforeDamage_OverwhelmSpillsMore
#//           control=N/A · reqboundary=AcrossTheRequestBoundary · modes=2P only (no player reference)
#//           Overwhelm = keyword-only half, auto-wired
#//
#// HMW_248 Defoliator Tank — Unit (Ground) 4/6, cost 5, [Villainy], Separatist/Vehicle/Tank.
#// "Overwhelm. On Attack: If the defending unit isn't a Droid or Vehicle, you may pay 2 resources. If you do,
#//  give 2 Weakness tokens to it."
#// Into SOR_046 (3/7): two Weakness → 1/5; the Tank deals 4 (survives at 1 left), takes 1.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_248:1:0
WithP1Resources: 2:SOR_046:1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# Decline_NoTokens

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_248:1:0
WithP1Resources: 2:SOR_046:1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P1RESAVAILABLE:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# OneResource_NoOffer

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_248:1:0
WithP1Resources: 1:SOR_046:1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1RESAVAILABLE:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# DroidDefender_NoOffer

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_248:1:0
WithP1Resources: 2:SOR_046:1
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1RESAVAILABLE:2
P2GROUNDARENACOUNT:0
P1NODECISION

---

# VehicleDefender_NoOffer
#// Another Defoliator Tank (Separatist/Vehicle/Tank) defends.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_248:1:0
WithP1Resources: 2:SOR_046:1
WithP2GroundArena: HMW_248:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1RESAVAILABLE:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# BaseAttack_NoOffer

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_248:1:0
WithP1Resources: 2:SOR_046:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1RESAVAILABLE:2
P1NODECISION

---

# WeaknessBeforeDamage_OverwhelmSpillsMore
#// SOR_095 (3/3) → 1/1 before damage: 4 into 1 HP spills 3 to the base (without the tokens it would be 1).

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_248:1:0
WithP1Resources: 2:SOR_046:1
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_248:1:0
WithP1Resources: 2:SOR_046:1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1RESAVAILABLE:0
