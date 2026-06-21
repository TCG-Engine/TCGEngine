# SEC_245 When Has Become Now — decline the (optional) Plot play; the ramp still happens.
# Same setup as the play case. P1 declines the Plot offer (AnswerDecision:-), so SEC_111 stays in the
# resource zone and no unit enters play — but the top of deck is still put into play as a ready resource.
# Net resource count goes 10 → 11 (nothing removed, +1 ramp), which distinguishes decline from the play
# path (which stays at 10).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 1:SEC_111:1,9:SOR_095:1
WithP1Hand: SEC_245
WithP1Deck: [SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:0
P1RESCOUNT:11
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
