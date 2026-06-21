# SEC_242 Elia Kane — "look at 3 enemy resources" caps at 3 even when more exist, and the Ready-first
# reveal rule picks WHICH 3. With 4 resources (3 ready + 1 exhausted) and a Credit, the 3 READY resources
# are the ones looked at (theirResources-0/1/2); the exhausted 4th and the last-kept Credit are not.
# P1 defeats one of the 3 ready; P2 replaces it from deck (ready), so P2 keeps 3 ready + 1 exhausted = 4
# real resources, the Credit untouched, deck −1, the defeated resource in discard.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:1,1:SOR_095:0
WithP2Credits: 1
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:4
P2RESAVAILABLE:3
P2CREDITCOUNT:1
P2DECKCOUNT:0
P2DISCARDCOUNT:1
