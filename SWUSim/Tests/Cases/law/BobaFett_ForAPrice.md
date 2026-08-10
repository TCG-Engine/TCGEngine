# OnAttackPayDeal3
#// LAW_214 Boba Fett (6/5) — When Played/On Attack: you may pay 1 resource. If you do, deal 3 damage to
#// a ground unit. Attacks the base; pay 1 -> deal 3 to the enemy SOR_046.

## GIVEN
CommonSetup: yyk/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: LAW_214:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayedPayDeal3_TargetsAnyGroundUnit
#// The When Played half of "When Played/On Attack: You may pay 1 resource. If you do, deal 3 damage to a
#// GROUND unit." The target set is any ground unit — friendly, enemy, or Boba himself — and no space
#// unit: SOR_244 Snowspeeder (friendly ground), LAW_214 Boba (self) and SOR_046 (enemy ground) are
#// offered, while SOR_212 Strafing Gunship and SOR_134 Ruthless Raider (both space) are not.
#// Resources: 7 - 5 (Boba) - 1 (the optional cost) = 1.

## GIVEN
CommonSetup: yyk/bgw/{myResources:7}
P1OnlyActions: true
WithP1Hand: LAW_214
WithP1GroundArena: SOR_244:1:0
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NODECISION
P1RESAVAILABLE:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:0

---

# WhenPlayedOfferIsGroundUnitsOnly
#// The offer itself, left pending: exactly the three ground units (friendly Snowspeeder, Boba himself,
#// the enemy unit) and neither space unit.

## GIVEN
CommonSetup: yyk/bgw/{myResources:7}
P1OnlyActions: true
WithP1Hand: LAW_214
WithP1GroundArena: SOR_244:1:0
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1DECISIONTOOLTIP:Deal_3_to_a_ground_unit
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# DecliningWhenPlayedKeepsTheResource
#// "You MAY pay 1 resource" — declining costs nothing and deals nothing. Boba still enters play; the
#// only resources spent are his own cost (7 - 5 = 2 ready), and no unit anywhere takes damage.

## GIVEN
CommonSetup: yyk/bgw/{myResources:7}
P1OnlyActions: true
WithP1Hand: LAW_214
WithP1GroundArena: SOR_244:1:0
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1RESAVAILABLE:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:0

---

# DecliningOnAttackKeepsTheResource
#// The same decline on the On Attack half. Boba attacks P2's base for his 6 power; all 5 resources stay
#// ready and no unit is damaged.

## GIVEN
CommonSetup: yyk/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: [SOR_244:1:0 LAW_214:1:0]
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:NO

## EXPECT
P1RESAVAILABLE:5
P2BASEDMG:6
P2GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0

---

# NoOfferOnAttackWithoutAResourceToPay
#// The optional cost is affordability-gated: with ZERO ready resources (and no Credits or Droids to pay
#// with) the ability never asks. Boba's attack resolves with no pending decision at all.

## GIVEN
CommonSetup: yyk/bgw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: [SOR_244:1:0 LAW_214:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1NODECISION
P2BASEDMG:6
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:0

---

# NoOfferWhenPlayedIfHisOwnCostUsedEveryResource
#// The same gate on the When Played half, and the sharper case: 5 resources is exactly Boba's cost, so
#// by the time his When Played resolves there is nothing left to pay the optional 1. No prompt appears.

## GIVEN
CommonSetup: yyk/bgw/{myResources:5}
P1OnlyActions: true
WithP1Hand: LAW_214
WithP1GroundArena: SOR_244:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
