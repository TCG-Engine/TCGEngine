# SEC_242 Elia Kane — when ALL of the opponent's resources are exhausted, there's no ready one to reveal
# preferentially, so the player can only defeat an exhausted resource. Its controller replaces it from
# deck as a READY resource — so the opponent ends with 1 ready resource (a "free" ready resource; the
# Ready-first reveal rule exists precisely to avoid handing this out when ready resources DO exist).
# P2 has 3 exhausted resources; P1 defeats one → P2 has 2 exhausted + 1 ready.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:0
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:1
P2DECKCOUNT:0
P2DISCARDCOUNT:1
