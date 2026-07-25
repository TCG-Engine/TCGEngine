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
