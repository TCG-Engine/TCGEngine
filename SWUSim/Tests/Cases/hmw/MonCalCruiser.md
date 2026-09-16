# BothModesOffered
#// COVERAGE: offer=this section (the mode menu) + LookMode_OffersTheirHand · decline=LookMode_DeclineDiscard
#//           gating=NoReadyUnit_LookModeResolvesAlone and EmptyOpponentHand_AttackModeResolvesAlone
#//           duration=AttackMode_BonusIsForThisAttackOnly · control=N/A · reqboundary=AcrossTheRequestBoundary
#//           modes=2P,TwinSuns (text says "an opponent") — TwinSuns_LooksAtTheChosenSeat
#//
#// HMW_232 Mon Cal Cruiser — Unit (Space) 4/7, cost 6, [Cunning], Rebel/Vehicle/Capital Ship.
#// "When Played: Choose one: Attack with a unit. It gets +2/+0 for this attack. —or— Look at an opponent's
#//  hand. You may discard a card from it. If you do, they draw a card."

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_232
WithP1GroundArena: SOR_095:1:0
WithP2Hand: [SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1OPTIONHAS:Attack
P1OPTIONHAS:LookAtHand

---

# AttackMode_PlusTwo

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_232
WithP1GroundArena: SOR_095:1:0
WithP2Hand: [SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Attack

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# AttackMode_BonusIsForThisAttackOnly

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_232
WithP1GroundArena: SOR_095:1:0
WithP2Hand: [SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Attack

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3

---

# NoReadyUnit_LookModeResolvesAlone

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_232
WithP1GroundArena: SOR_095:0:0
WithP2Hand: [SOR_095 SEC_080]
WithP2Deck: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-1

## EXPECT
P2DISCARDCOUNT:1
P2HANDCOUNT:2
P2DECKCOUNT:0

---

# LookMode_OffersTheirHand

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_232
WithP1GroundArena: SOR_095:1:0
WithP2Hand: [SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LookAtHand

## EXPECT
P1SELECTABLEEXACT:theirHand-0&theirHand-1

---

# LookMode_DeclineDiscard

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_232
WithP2Hand: [SOR_095 SEC_080]
WithP2Deck: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P2DECKCOUNT:1

---

# EmptyOpponentHand_AttackModeResolvesAlone

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_232
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5

---

# TwinSuns_LooksAtTheChosenSeat

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: HMW_232
WithP2Hand: [SOR_095 SEC_080]
WithP3Hand: [SOR_095 SEC_080]
WithP3Deck: [SOR_046 SOR_128]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3
- P1>AnswerDecision:p3Hand-0

## EXPECT
SEATCOUNT:4
P3DISCARDCOUNT:1
P3HANDCOUNT:2
P2HANDCOUNT:2
P2DISCARDCOUNT:0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_232
WithP1GroundArena: SOR_095:1:0
WithP2Hand: [SOR_095 SEC_080]
WithP2Deck: SOR_046

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:LookAtHand
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirHand-0

## EXPECT
P2DISCARDCOUNT:1
P2HANDCOUNT:2
