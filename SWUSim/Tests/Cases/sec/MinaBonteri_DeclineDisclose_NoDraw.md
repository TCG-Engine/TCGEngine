# SEC_094 Mina Bonteri — decline the When Defeated disclose → no draw.
# Same defeat as the positive test; P1 declines (AnswerDecision:-) so the deck/hand are unchanged
# (hand stays at the 2 fodder cards, deck at 2).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_094:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SEC_096
WithP1Hand: SEC_080
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:2
P2NODECISION
