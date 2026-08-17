# OnAttackDiscardToBottomCredit
#// LAW_238 Scavenging Sandcrawler (1/7) — On Attack: you may put a card from your discard on the bottom
#// of your deck. If you do, create a Credit token. Attacks the base; put SOR_237 on the bottom -> 1 Credit.

## GIVEN
CommonSetup: yyk/bgw/{discardCardIds:SOR_237}
P1OnlyActions: true
WithP1GroundArena: LAW_238:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1CREDITCOUNT:1
P1DISCARDCOUNT:0
P1DECKCOUNT:1

---

# PassDoesNothing
#// LAW_238 Scavenging Sandcrawler — the On Attack ability is optional; declining it puts nothing on the
#// deck bottom and creates no Credit. Attacks the base and passes; discard and Credit count are unchanged.

## GIVEN
CommonSetup: yyk/bgw/{discardCardIds:SOR_237}
P1OnlyActions: true
WithP1GroundArena: LAW_238:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P1CREDITCOUNT:0
P1DISCARDCOUNT:1
P1DECKCOUNT:0

---

# ControlledUnit_OfferComesFromControllersDiscard
#// COVERAGE: control=ControlledUnit_OfferComesFromControllersDiscard +
#//           ControlledUnit_BottomsControllersDeckAndCreditsController (the Sandcrawler sits in P1's
#//           ground arena but is OWNED by P2; "your discard pile", "your deck" and the Credit must all
#//           land on the CONTROLLER's seat) · offer=this section (both discards hold the SAME two cards,
#//           so only the mzID frame separates them, and two candidates stop it auto-resolving) ·
#//           decline=PassDoesNothing · reqboundary=N/A (single On Attack decision, nothing re-read after).
#//
#// LAW_238 Scavenging Sandcrawler — owner ≠ controller. P1 CONTROLS the Sandcrawler, P2 OWNS it. Each
#// player's discard pile holds SOR_237 and SOR_095, so a pool taken from the wrong seat would look just
#// as legal; exactly P1's two entries may be selectable.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArenaControlled: LAW_238:2
WithP1Discard: [SOR_237 SOR_095]
WithP2Discard: [SOR_237 SOR_095]
WithP1Deck: SOR_128
WithP2Deck: [SOR_225 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1

---

# ControlledUnit_BottomsControllersDeckAndCreditsController
#// LAW_238 Scavenging Sandcrawler — the resolution half. P1 controls the P2-OWNED Sandcrawler and puts
#// SOR_237 from P1's discard on the bottom of P1's deck: P1's discard falls 2 → 1 and P1's deck rises
#// 1 → 2, while P2's identical discard (2) and deck (2) never move. The Credit created by "if you do"
#// belongs to the CONTROLLER — P1 has one, P2 has none — and the 1 combat damage lands on P2's base, not
#// on the base of the unit's owner.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArenaControlled: LAW_238:2
WithP1Discard: [SOR_237 SOR_095]
WithP2Discard: [SOR_237 SOR_095]
WithP1Deck: SOR_128
WithP2Deck: [SOR_225 SOR_225]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:2
P1DECKCOUNT:2
P2DECKCOUNT:2
P2BASEDMG:1
P1BASEDMG:0
