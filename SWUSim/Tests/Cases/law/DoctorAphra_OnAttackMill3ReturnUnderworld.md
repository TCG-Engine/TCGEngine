# LAW_194 Doctor Aphra (4/5) — On Attack: discard 3 from your deck. You may return an Underworld card
# discarded this way to your hand. Mill LAW_124 (Underworld) + 2 SOR_237 -> return LAW_124.

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
