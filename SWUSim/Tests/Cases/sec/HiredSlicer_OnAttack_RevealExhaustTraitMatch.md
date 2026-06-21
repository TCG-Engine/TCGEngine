# SEC_220 Hired Slicer (Unit, 3/4, cost 3, Cunning, Fringe, Ground)
#   "On Attack: Reveal the top 2 cards of a deck. If you do, you may exhaust a unit that shares a Trait
#    with one of those cards. Put those cards on the bottom of that deck in a random order."
# SEC_220 attacks P2's base. On Attack: P1 reveals the top 2 of its OWN deck (both SOR_095 = Rebel/Trooper),
# then exhausts the friendly SOR_095 (a Rebel/Trooper, sharing a trait). The 2 revealed cards go back to the
# bottom (deck count returns to 2). SEC_220 itself is Fringe, so it is NOT a legal exhaust target — only the
# Rebel/Trooper SOR_095 is offered.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_220:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1DECKCOUNT:2
P1NODECISION
