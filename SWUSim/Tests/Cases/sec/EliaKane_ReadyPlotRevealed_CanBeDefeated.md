# SEC_242 Elia Kane — a Plot card left READY gets no protection: it's revealed like any ready resource
# and can be defeated. P2 has 2 exhausted resources + 1 ready Plot card (SEC_053). All 3 are revealed (only
# 3 exist); P1 defeats the ready Plot (theirResources-2). It goes to P2's discard and P2 replaces it from
# deck with a ready resource — so P2 ends with 2 exhausted + 1 ready, the Plot now in discard, deck −1.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 2:SOR_095:0,1:SEC_053:1
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-2

## EXPECT
P2RESCOUNT:3
P2RESAVAILABLE:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SEC_053
P2DECKCOUNT:0
