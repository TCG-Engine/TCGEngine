# OnAttack_CantAfford_NoOffer
#// SOR_206 Mining Guild TIE Fighter — the draw is gated on paying 2 resources. With only 1
#// ready resource the option isn't offered: the attack resolves with no decision pending, no
#// resources spent, and no card drawn. Unaffordable-cost guard.
#// COVERAGE: offer=N/A — the ability targets nothing: it is a YESNO pay-or-not with no candidate pool
#//           to inspect. The equivalent scope assertion is whether the OFFER APPEARS AT ALL, and that
#//           is discriminated by this section (1 resource -> P1NODECISION, nothing offered) against
#//           OnAttack_ExactlyTwoResources_OfferedAndFullyPaid (2 resources -> raised) ·
#//           reqboundary=SimulateRequestBoundary_PayOfferSurvives · control=ControlTakenFighter_The
#//           CONTROLLERPaysAndDraws (owner differs from controller; the On Attack resolves from the
#//           CONTROLLER's seat, so their resources and their deck are the ones that move) ·
#//           boundary=this section (1 ready resource, no offer) vs OnAttack_ExactlyTwoResources_...
#//           (exactly 2, offered and fully paid) — the N vs N-1 pair on the pay threshold ·
#//           decline=OnAttack_DeclineNO_NoPayNoDraw ("you MAY pay": NO leaves the resources and the
#//           deck untouched while the attack itself still lands).

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1SpaceArena: SOR_206:1:0
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1NODECISION
P1RESAVAILABLE:1
P1HANDCOUNT:0

---

# OnAttack_Pay2Draw
#// SOR_206 Mining Guild TIE Fighter (1/2, Space) — On Attack: You may pay 2 resources. If you
#// do, draw a card. P1 attacks the base with 3 ready resources; choosing YES pays 2 (→ 1 ready)
#// and draws 1 card.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1SpaceArena: SOR_206:1:0     # Mining Guild TIE (ready) — attacker
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:1
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# OnAttack_DeclineNO_NoPayNoDraw
#// SOR_206 Mining Guild TIE Fighter — the decline branch of "You MAY pay [2 resources]". P1 has 3 ready
#// resources, so the YESNO IS raised (this is not the unaffordable case), and P1 answers NO. Nothing is
#// paid and nothing is drawn: 3 ready resources still, hand empty, deck untouched at 2. The attack
#// itself is unaffected — the TIE's 1 power still reaches P2's base — because the pay is a rider on the
#// On Attack window, not a cost of attacking.
#// This is the load-bearing negative for OnAttack_Pay2Draw: without it a handler that paid and drew
#// regardless of the answer would pass every other section in this file.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1SpaceArena: SOR_206:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1RESAVAILABLE:3
P1HANDCOUNT:0
P1DECKCOUNT:2
P2BASEDMG:1
P1NODECISION

---

# OnAttack_ExactlyTwoResources_OfferedAndFullyPaid
#// SOR_206 Mining Guild TIE Fighter — the N vs N-1 boundary against OnAttack_CantAfford_NoOffer, which
#// seats ONE ready resource and proves the offer is suppressed. Here P1 has EXACTLY 2: the cost is
#// affordable, so the YESNO is raised, YES pays the whole pool down to 0 ready and draws.
#// Two resources is the exact threshold; one is not. An off-by-one in the affordability gate ("< 2"
#// written as "<= 2") shows up here as a missing prompt and nowhere else.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SOR_206:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:0
P1RESCOUNT:2
P1HANDCOUNT:1
P1DECKCOUNT:1
P2BASEDMG:1

---

# ControlTakenFighter_TheCONTROLLERPaysAndDraws
#// SOR_206 Mining Guild TIE Fighter × a control change. P1 CONTROLS a TIE Fighter that P2 OWNS (the end
#// state after a take-control effect) and attacks with it. "You may pay 2 resources … draw a card"
#// names no player, so per CR the ability's controller resolves it: P1's resources are spent (3 -> 1
#// ready) and P1 draws from P1's deck. P2's resources and deck must be untouched — an implementation
#// that resolved a unit's On Attack from the OWNER's seat would empty the wrong pool and put the card
#// in the wrong hand, and every same-seat section in this file would still be green.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3;theirResources:3}
P1OnlyActions: true
WithP1SpaceArenaControlled: SOR_206:2
WithP1Deck: SOR_128
WithP1Deck: SOR_128
WithP2Deck: SOR_237
WithP2Deck: SOR_237

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:1
P1HANDCOUNT:1
P1DECKCOUNT:1
P2RESAVAILABLE:3
P2HANDCOUNT:0
P2DECKCOUNT:2
P2BASEDMG:1

---

# SimulateRequestBoundary_PayOfferSurvives
#// SOR_206 Mining Guild TIE Fighter — in production the pay YESNO ENDS the request: the answer arrives
#// in a fresh process where every non-serialized global is empty, and the attack it is riding on is
#// already mid-resolution. Mirrors OnAttack_Pay2Draw with the boundary inserted before the answer, so
#// the pending YESNO and its SOR_206#0 continuation must both survive a gamestate round-trip and still
#// pay from the right seat.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1SpaceArena: SOR_206:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:1
P1HANDCOUNT:1
P1DECKCOUNT:1
P2BASEDMG:1
