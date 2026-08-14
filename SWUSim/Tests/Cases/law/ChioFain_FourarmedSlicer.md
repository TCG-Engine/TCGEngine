# OnAttackBothDraw
#// LAW_048 Chio Fain (2/4) — On Attack: you may choose 2 players. If you do, they each draw a card.
#// (2-player: both players draw.)
#// COVERAGE: offer=N/A (YES/NO "you may" prompt, not a target-choice; in a 2-player game "choose 2
#//           players" has exactly one selection) · decline=OnAttack_DeclineNoDraws · boundary=deck
#//           non-empty vs empty pair: OnAttackBothDraw + OnAttack_EmptyOpponentDeck_ThreeDamageToTheirBase
#//           (failed draw = 3 to that player's own base) · control=N/A (players, not units, are chosen)
#//           · reqboundary=N/A (single YES/NO decision; draws resolve in the same window)

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_048:1:0
WithP1Deck: SOR_237
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:1

---

# OnAttack_DeclineNoDraws
#// Intended: the draw is "you may" — declining leaves both hands untouched; the attack itself still
#// deals Chio's 2 to the base.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_048:1:0
WithP1Deck: SOR_237
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
P1DECKCOUNT:1
P2DECKCOUNT:1
P2BASEDMG:2

---

# OnAttack_EmptyOpponentDeck_ThreeDamageToTheirBase
#// Intended: with P2's deck empty, accepting the draw still draws P1's card, while P2 cannot draw and
#// instead takes 3 damage to their base (CR: a player who cannot draw deals 3 damage to their own base
#// per undrawn card). P2 base ends at 5: 2 from Chio's attack + 3 from the failed draw.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_048:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_237
P2HANDCOUNT:0
P2BASEDMG:5
