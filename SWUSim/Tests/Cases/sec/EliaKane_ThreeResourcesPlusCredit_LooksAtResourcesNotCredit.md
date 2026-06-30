# SEC_242 Elia Kane — "look at 3 enemy resources." When the opponent has 3 resources AND a Credit token,
# the Credit is kept LAST in the resource zone, so the 3 looked-at (and offerable) cards are the real
# resources — the Credit is never offered. P1 defeats one resource (theirResources-0); P2 replaces it from
# deck (ready). The Credit is untouched (P2 still has 1), the defeated resource goes to discard, deck −1.
# (Real resources stay at 3 because P2RESCOUNT excludes Credit tokens.)

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:1
WithP2Credits: 1
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:3
P2CREDITCOUNT:1
P2DECKCOUNT:0
P2DISCARDCOUNT:1
