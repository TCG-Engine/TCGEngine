# OnAttack_CantAfford_NoOffer
#// SOR_206 Mining Guild TIE Fighter — the draw is gated on paying 2 resources. With only 1
#// ready resource the option isn't offered: the attack resolves with no decision pending, no
#// resources spent, and no card drawn. Unaffordable-cost guard.

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
