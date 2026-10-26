# Accept_PaysOneDiscards_NextUnitCostsThreeLess
#// IC27_145 Poggle the Lesser (Ground 3/5) — "On Attack: You may pay [1 resource] and discard a card
#// from your hand. If you do, the next card you play this phase costs [3 resources] less."
#// Poggle attacks P2's base; P1 accepts, pays 1 (6 → 5 ready), discards the TIE/ln, then plays Swoop
#// Racer (cost 3) for 0 — 5 ready resources remain (a −2 discount would leave 4). The charge is spent.
#// COVERAGE: offer=Offer_TheDiscardPickIsTheWholeHand_AndTheOneIsAlreadyPaid (pending SELECTABLEEXACT) ·
#//           decline=Decline_NoPayment_NoDiscard_FullPrice · cannot-pay=NoPaymentCapacity_NoOffer and
#//           EmptyHand_NoOffer (each half of "pay AND discard" gates the offer on its own) ·
#//           boundary=the amount 3: this section (cost 3 → 0, separates −3 from −2) and
#//           NextEventAlsoDiscounted (cost 4 → 1, separates −3 from −4) ·
#//           control=ControlChange_TheControllerPaysDiscardsAndGetsTheCharge ·
#//           reqboundary=Accept_AnsweredAcrossRequestBoundaries · duration=ExpiresAtTheEndOfThePhase +
#//           OnlyTheNextCard_EvenWhenItCostsLessThanThree
#//           modes=2P only (no player reference, no friendly/enemy wording — "your hand" is the controller's)

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: IC27_145:1:0
WithP1Hand: [SOR_210 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:myHand-1
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_210
P1RESAVAILABLE:5
P1NOGLOBALEFFECT:SWU_IC27145_DISCOUNT_NEXT

---

# Offer_TheDiscardPickIsTheWholeHand_AndTheOneIsAlreadyPaid
#// After "yes" the 1 resource is already paid and the discard pick is mandatory over every card in hand.
#// The pick is left PENDING.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: IC27_145:1:0
WithP1Hand: [SOR_210 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1
P1RESAVAILABLE:5
P1HANDCOUNT:2

---

# NextEventAlsoDiscounted
#// "the next CARD" is any type, not just a unit: On the Doorstep (event, cost 4) costs 1 after the
#// discount — 5 ready → 4 (a −4 discount would leave 5). Its three Battle Droids are created.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: IC27_145:1:0
WithP1Hand: [TWI_190 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:myHand-1
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:4
P1GROUNDARENACOUNT:4
P1HANDCOUNT:0
P1NOGLOBALEFFECT:SWU_IC27145_DISCOUNT_NEXT

---

# Decline_NoPayment_NoDiscard_FullPrice
#// "You may" — declining pays nothing, discards nothing, arms nothing: Swoop Racer then costs its full 3.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: IC27_145:1:0
WithP1Hand: [SOR_210 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1DISCARDCOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:3
P1NOGLOBALEFFECT:SWU_IC27145_DISCOUNT_NEXT

---

# NoPaymentCapacity_NoOffer
#// With no ready resource (and no Credit) the "pay [1 resource]" half cannot be done, so the offer is
#// never raised — accepting could only throw away a card. The hand is untouched.

## GIVEN
CommonSetup: yyk/bbw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: IC27_145:1:0
WithP1Hand: [SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:3
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1NOGLOBALEFFECT:SWU_IC27145_DISCOUNT_NEXT

---

# EmptyHand_NoOffer
#// With an empty hand the "discard a card" half cannot be done, so the offer is never raised — accepting
#// could only spend a resource for nothing. All 6 resources stay ready.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: IC27_145:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:3
P1RESAVAILABLE:6
P1NOGLOBALEFFECT:SWU_IC27145_DISCOUNT_NEXT

---

# CreditPaysTheOne
#// A Credit token pays an ability's resource cost (CR 3.13): with 0 ready resources and 1 Credit the
#// offer IS raised, the Credit is spent, the lone hand card is discarded (single candidate auto-resolves)
#// and the charge is armed.

## GIVEN
CommonSetup: yyk/bbw/{myResources:0}
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: IC27_145:1:0
WithP1Hand: [SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1CREDITCOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1GLOBALEFFECT:SWU_IC27145_DISCOUNT_NEXT

---

# OnlyTheNextCard_EvenWhenItCostsLessThanThree
#// The discount applies to the NEXT card only, and that card spends it even though it costs less than 3:
#// the TIE/ln (cost 1) is free, then Swoop Racer pays its full 3. 6 → 5 (Poggle) → 5 → 2.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: IC27_145:1:0
WithP1Hand: [SOR_225 SOR_210 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:myHand-2
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:2
P1HANDCOUNT:0
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:2
P1NOGLOBALEFFECT:SWU_IC27145_DISCOUNT_NEXT

---

# TwoChargesStack_OnTheOneNextCard
#// Two "the next card costs 3 less" effects armed before a play both apply to that card and both are
#// spent (the TS26_35 precedent). One charge is pre-armed; Poggle arms the second; On the Doorstep (cost 4)
#// then costs 0 (4 − 6, floored) — 5 ready remain — and no charge is left over.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
P1OnlyActions: true
WithP1GlobalEffect: SWU_IC27145_DISCOUNT_NEXT
WithP1GroundArena: IC27_145:1:0
WithP1Hand: [TWI_190 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:myHand-1
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:5
P1GROUNDARENACOUNT:4
P1NOGLOBALEFFECT:SWU_IC27145_DISCOUNT_NEXT

---

# ExpiresAtTheEndOfThePhase
#// DURATION EDGE: "this phase". The charge is armed but nothing is played; the round turns over, and in
#// the next action phase Swoop Racer costs its full 3 (6 ready after the regroup → 3).

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: IC27_145:1:0
WithP1Hand: [SOR_210 SOR_225]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:myHand-1
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_210
P1RESAVAILABLE:3
P1NOGLOBALEFFECT:SWU_IC27145_DISCOUNT_NEXT

---

# Accept_AnsweredAcrossRequestBoundaries
#// The happy path with a FRESH process before the discard pick and again before the play: the paid
#// resource, the pending pick and the armed charge must all survive serialization.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: IC27_145:1:0
WithP1Hand: [SOR_210 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-1
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:5
P1NOGLOBALEFFECT:SWU_IC27145_DISCOUNT_NEXT

---

# ControlChange_TheControllerPaysDiscardsAndGetsTheCharge
#// Poggle is OWNED by P1 but CONTROLLED by P2. "you" / "your hand" is the controller: P2 pays from P2's
#// resources, discards from P2's hand, and the charge is P2's. P1's hand, discard and charges are untouched.

## GIVEN
CommonSetup: yyk/yyk/{theirResources:6}
WithActivePlayer: 2
WithP2GroundArenaControlled: IC27_145:1
WithP1Hand: [SOR_225]
WithP2Hand: [SOR_210 SOR_225]

## WHEN
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES
- P2>AnswerDecision:myHand-1

## EXPECT
P1BASEDMG:3
P2DISCARDCOUNT:1
P2HANDCOUNT:1
P2RESAVAILABLE:5
P2GLOBALEFFECT:SWU_IC27145_DISCOUNT_NEXT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1NOGLOBALEFFECT:SWU_IC27145_DISCOUNT_NEXT
