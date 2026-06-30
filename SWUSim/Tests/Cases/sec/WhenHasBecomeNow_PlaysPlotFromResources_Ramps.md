# SEC_245 When Has Become Now (Event, cost 1, Villainy, Trick)
#   "Play a card with Plot from your resources (paying its cost). Put the top card of your deck into
#    play as a resource."
# P1 plays SEC_245 (rk leader covers Villainy → cost 1). Its resources hold SEC_111 (a cost-2 Command
# Plot unit; off-aspect → effective cost 4, easily covered by 10 ready). The MZMAYCHOOSE offers SEC_111;
# P1 plays it (it enters the ground arena and leaves the resource zone), then the top of deck (SOR_095)
# is put into play as a ready resource. Net resource count unchanged (−1 played Plot, +1 ramp).
# (SEC_245 does NOT trigger the Plot keyword's deploy-replace — its own ramp clause is the refill.)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 1:SEC_111:1,9:SOR_095:1
WithP1Hand: SEC_245
WithP1Deck: [SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_111
P1DECKCOUNT:0
P1RESCOUNT:10
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
