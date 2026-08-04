# PayReturnOwnUnit_ThenPlayItFree
#// IC27_158 Millennium Falcon (YA-HOO!) — 4 cost, 4/4, Cunning+Heroism, SPACE, Rebel/Vehicle/Transport.
#// Text: "When Attack Ends: You may pay [1 resource]. If you do, return a friendly unit that costs 3
#//   or less to its owner's hand. If it's returned to your hand, you may play it for free."
#// The full happy path: pay 1 -> the only legal target (a cost-2 Marine) auto-resolves -> it returns to
#// MY hand -> replay it for free. Net resources: 3 -> 2 (the payment only; the replay is free).
#// The Falcon itself costs 4, so it is NOT a legal target — proven by the choose auto-resolving to the
#// Marine and the Falcon still sitting in space afterwards.

## GIVEN
CommonSetup: yyw/yyw/{}
P1OnlyActions: true
WithP1SpaceArena: IC27_158:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 3:SOR_046:1

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1RESAVAILABLE:2
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:IC27_158
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1HANDCOUNT:0

---

# SurvivesRequestBoundariesBetweenEveryDecision
#// REQUEST BOUNDARY — the JTL_094 Luke bug class. This ability spans three interactive decisions, and
#// in production every one of them ends the HTTP request: anything parked in an in-memory global
#// between them would be EMPTY when the answer arrives, and the handler would return silently (the
#// card just doing nothing). The single-process suite cannot see that on its own, so force a
#// WriteGamestate/ParseGamestate round-trip before each answer and assert the full chain still lands.

## GIVEN
CommonSetup: yyw/yyw/{}
P1OnlyActions: true
WithP1SpaceArena: IC27_158:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 3:SOR_046:1

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1HANDCOUNT:0

---

# DeclineThePayment_NothingHappens
#// TAKE/DECLINE on the first branch: refusing to pay leaves resources and the board untouched.

## GIVEN
CommonSetup: yyw/yyw/{}
P1OnlyActions: true
WithP1SpaceArena: IC27_158:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 3:SOR_046:1

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1RESAVAILABLE:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1HANDCOUNT:0

---

# DeclineTheFreePlay_UnitStaysInHand
#// TAKE/DECLINE on the SECOND branch: the return is mandatory once paid ("If you do"), but the replay
#// is its own "may". Declining leaves the unit in hand and the resource still spent.

## GIVEN
CommonSetup: yyw/yyw/{}
P1OnlyActions: true
WithP1SpaceArena: IC27_158:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 3:SOR_046:1

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:NO

## EXPECT
P1RESAVAILABLE:2
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# CostFourUnitNotEligible_NoOfferAtAll
#// BOUNDARY 3/4 + no-valid-target: the only other friendly unit costs 4, so nothing can be returned
#// and the pay-offer must not appear — paying a resource for a guaranteed no-op is not a choice worth
#// presenting.

## GIVEN
CommonSetup: yyw/yyw/{}
P1OnlyActions: true
WithP1SpaceArena: IC27_158:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Resources: 3:SOR_046:1

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1NODECISION
P1RESAVAILABLE:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046

---

# NoPaymentCapacity_NoOffer
#// The cost gate: with every resource exhausted the player cannot pay, so no prompt is raised.
#// Gated on SWUTotalPaymentCapacity (ready resources + Credits + SEC_122 Droids), not a bare ready
#// count — the JTL_096 Blue Leader bug family.

## GIVEN
CommonSetup: yyw/yyw/{}
P1OnlyActions: true
WithP1SpaceArena: IC27_158:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 3:SOR_046:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1NODECISION
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1

---

# EnemyOwnedUnit_ReturnsToOwnersHand_AndNoFreePlay
#// THE HEADLINE NEGATIVE — the two clauses read DIFFERENT properties. The return is to its OWNER's
#// hand, but the free replay is gated on "if it's returned to YOUR hand". Controlling a unit the
#// opponent OWNS makes those diverge: it goes to P2's hand and P1 gets no replay offer at all.
#// An implementation that used the controller for both would wrongly hand P1 a free unit.

## GIVEN
CommonSetup: yyw/yyw/{}
P1OnlyActions: true
WithP1SpaceArena: IC27_158:1:0
WithP1GroundArenaControlled: SOR_095:2
WithP1Resources: 3:SOR_046:1

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:2
P1GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1HANDCOUNT:0
P1NODECISION

---

# TriggersOnAUnitAttackToo
#// DISPATCH: "When Attack Ends" is unconditional — it does not require a base attack or a defeated
#// defender. The Falcon (4 power) kills a 2/1 TIE and takes 2 back, and the offer still resolves.

## GIVEN
CommonSetup: yyw/yyw/{}
P1OnlyActions: true
WithP1SpaceArena: IC27_158:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 3:SOR_046:1

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
