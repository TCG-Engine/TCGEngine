# OnAttackDiscardCredit
#// LAW_236 Bix Caleen (4/5) — When Played/On Attack: you may discard a card from your hand. If you do,
#// create a Credit token. Attacks the base; discard SOR_237 -> 1 Credit.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_236:1:0
WithP1Hand: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0

## EXPECT
P1CREDITCOUNT:1
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# WhenPlayedDiscardCredit
#// LAW_236 Bix Caleen (4/5, Cunning) — When Played: you may discard a card from hand; if you do, create a
#// Credit token. Play Bix, discard SOR_237 -> 1 Credit, hand empties.

## GIVEN
CommonSetup: yyk/bgw/{myResources:4}
WithP1Hand: [LAW_236 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1CREDITCOUNT:1
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# WhenPlayedPass
#// LAW_236 Bix Caleen — When Played: the discard is optional ("you may"); passing creates no Credit and
#// keeps the card in hand.

## GIVEN
CommonSetup: yyk/bgw/{myResources:4}
WithP1Hand: [LAW_236 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1CREDITCOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:0

---

# WhenPlayedEmptyHand
#// LAW_236 Bix Caleen — When Played: with no other card in hand, the ability can't discard and creates no
#// Credit.

## GIVEN
CommonSetup: yyk/bgw/{myResources:4}
WithP1Hand: LAW_236

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:0
P1HANDCOUNT:0

---

# OnAttackPass
#// LAW_236 Bix Caleen — On Attack: the discard is optional; passing creates no Credit and keeps the card.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_236:1:0
WithP1Hand: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1CREDITCOUNT:0
P1HANDCOUNT:1

---

# OnAttackEmptyHand
#// LAW_236 Bix Caleen — On Attack: with an empty hand the ability can't discard and creates no Credit.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_236:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:0

---

# DiscardPool_OwnHandOnly
#// COVERAGE: offer=DiscardPool_OwnHandOnly (the "a card from your hand" pool asserted exactly — both of
#//           P1's own hand cards IN, both of P2's hand cards OUT); offer-absence = WhenPlayedEmptyHand /
#//           OnAttackEmptyHand · decline=WhenPlayedPass + OnAttackPass ("you may", declined on both
#//           trigger halves) · control=N/A (no control-change text; the Credit is seat-bound) ·
#//           boundary=WhenPlayedDiscardCredit vs WhenPlayedPass and OnAttackDiscardCredit vs OnAttackPass
#//           (discard taken vs declined on each half), plus the empty-hand pair · reqboundary=every
#//           positive section answers the discard pick in a request after the play/attack that queued it.
#// LAW_236 Bix Caleen — "When Played/On Attack: You may discard A CARD FROM YOUR HAND." The only
#// restriction is the zone-plus-controller scope "your hand", and the fixtures so far all hold exactly one
#// card, so the pick has never been readable. Here P1 holds TWO cards (both must be IN) while P2 holds two
#// of its own (both must be OUT) — a pool that leaked the opponent's hidden zone, or that offered cards in
#// play, would be invisible to every existing section. Because the clause is a "you may", the offer stays
#// pending even though both legal answers belong to the same player.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_236:1:0
WithP1Hand: SOR_237
WithP1Hand: SOR_095
WithP2Hand: SOR_046
WithP2Hand: SEC_080

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1HANDCOUNT:2
P2HANDCOUNT:2
P1SELECTABLEEXACT:myHand-0&myHand-1
