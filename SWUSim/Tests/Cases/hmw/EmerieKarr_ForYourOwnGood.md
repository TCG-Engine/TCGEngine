# Offer_AnotherGroundUnitEitherSide
#// COVERAGE: offer=this section · decline=Decline_NoDamageNoDiscount
#//           boundary=N/A (STRUCTURAL: fixed amounts) · gate=DamageAnEnemy_NoDiscount (the "if you control" negative)
#//           quantity=DiscountIsSpentOnce · lki=DefeatedFriendly_StillDiscounts
#//           control=N/A ("you control" is read on the chosen unit) · reqboundary=AcrossTheRequestBoundary
#//           modes=2P only ("you control" is self-only; "another ground unit" has no friendly/enemy wording)
#//
#// HMW_130 Emerie Karr — Unit (Ground) 2/1, cost 1, [Command], Imperial/Clone.
#// "When Played: You may deal 1 damage to another ground unit. If you control that unit, the next unit you
#//  play this phase costs 1 resource less."
#// P1 SOR_095 at ground 0; Emerie lands at ground 1; P2's space TIE is not a ground unit.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_130
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# DamageYourOwnUnit_NextUnitCostsOneLess
#// 2 resources: Emerie (1), then SOR_095 (2) for 1.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_130 SOR_095]
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:0

---

# DamageAnEnemy_NoDiscount

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_130 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1HANDCOUNT:1
P1RESAVAILABLE:1

---

# Decline_NoDamageNoDiscount

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_130 SOR_095]
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:1

---

# DiscountIsSpentOnce
#// 4 resources: Emerie (1), SOR_095 (2-1=1), SOR_095 (2) = exactly 4.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_130 SOR_095 SOR_095]
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:4
P1RESAVAILABLE:0

---

# DefeatedFriendly_StillDiscounts
#// SOR_128 Death Star Stormtrooper (3/1) dies to the 1 damage — it was yours when chosen.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_130 SOR_095]
WithP1GroundArena: [SOR_128:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [HMW_130 SOR_095]
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1HANDCOUNT:0
