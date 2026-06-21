# SEC_242 Elia Kane — the Ready-first reveal protects a Plot card kept EXHAUSTED. P2 has 3 ready
# resources + 1 exhausted Plot card (SEC_053). Elia Kane reveals the 3 ready ones; the exhausted Plot is
# NOT revealed. Since you can only defeat a REVEALED resource, P1's attempt to defeat the Plot
# (theirResources-3) is rejected — the Plot survives, nothing is defeated and nothing is replaced. This is
# the incentive to keep your Smuggle/Plot cards exhausted.

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP2Resources: 3:SOR_095:1,1:SEC_053:0
WithP2Deck: SEC_080
WithP1Hand: SEC_242

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-3

## EXPECT
P2RESCOUNT:4
P2RESAVAILABLE:3
P2DISCARDCOUNT:0
P2DECKCOUNT:1
