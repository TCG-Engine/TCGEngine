# OnAttackMill3ReturnUnderworld
#// LAW_194 Doctor Aphra (4/5) — On Attack: discard 3 from your deck. You may return an Underworld card
#// discarded this way to your hand. Mill LAW_124 (Underworld) + 2 SOR_237 -> return LAW_124.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_194:1:0
WithP1Deck: LAW_124
WithP1Deck: SOR_237
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:2

---

# OnAttackMill3DeclineReturn
#// LAW_194 Doctor Aphra (4/5) — On Attack: discard 3, then "you may return an Underworld card discarded
#// this way." Declining leaves all 3 milled cards in the discard pile and returns nothing.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_194:1:0
WithP1Deck: LAW_124
WithP1Deck: SOR_237
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:3

---

# OnAttackFewerThan3Cards
#// LAW_194 Doctor Aphra (4/5) — On Attack works when fewer than 3 cards are left to discard. Deck has only
#// LAW_124 (Underworld) + SOR_237; both are milled and the Underworld card can be returned.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_194:1:0
WithP1Deck: LAW_124
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:1

---

# OnAttackNoUnderworldDiscarded
#// LAW_194 Doctor Aphra (4/5) — On Attack: when none of the 3 milled cards are Underworld there is no return
#// prompt; play moves on with all 3 cards in the discard pile.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_194:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_237
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:3

---

# OnAttackEmptyDeckNoError
#// LAW_194 Doctor Aphra (4/5) — On Attack with an empty deck: nothing is discarded and no return prompt
#// appears; play resolves without error.

## GIVEN
CommonSetup: rrw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_194:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:0
