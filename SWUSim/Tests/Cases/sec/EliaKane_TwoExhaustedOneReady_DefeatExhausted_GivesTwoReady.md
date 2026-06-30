# SEC_242 Elia Kane — opponent has 2 exhausted + 1 ready (all 3 presented). Defeating an EXHAUSTED one
# (theirResources-0) replaces it from deck with a READY resource, so the opponent now has 2 ready (the
# original ready + the new replacement) — a free ready resource for the opponent. This is the outcome the
# Ready-first reveal rule avoids when ready resources exist (it would only offer ready ones to defeat).

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 2:SOR_095:0,1:SOR_095:1
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:2
P2DECKCOUNT:0
P2DISCARDCOUNT:1
