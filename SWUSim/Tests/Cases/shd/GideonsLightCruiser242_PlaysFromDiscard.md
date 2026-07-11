# SHD_242 Gideon's Light Cruiser — free-play the Villainy <=3 unit from the DISCARD pile.
# P1 controls Moff Gideon deployed. SEC_080 (cost 2, Villainy) sits in P1's discard. Playing SHD_242
# offers it; P1 picks myDiscard-0 and it enters play for free (discard empties, ground count = Gideon + SEC_080).

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;myLeader:SHD_007:1:1;discardCardIds:SEC_080}
P1OnlyActions: true
WithP1Hand: SHD_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:2
P1DISCARDCOUNT:0
P1RESAVAILABLE:2
