# SEC_242 Elia Kane — opponent has 2 exhausted + 1 ready resource (3 total, so all 3 are presented).
# Defeating the READY one (theirResources-2): it's replaced from deck with another ready resource, so the
# opponent STILL has just 1 ready (no free upgrade). This is the "correct" target — it doesn't hand the
# opponent a free ready resource.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 2:SOR_095:0,1:SOR_095:1
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-2

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:1
P2DECKCOUNT:0
P2DISCARDCOUNT:1
