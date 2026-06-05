# SOR_183 Bounty Hunter Crew — the event returns to its OWNER's hand, even from the OPPONENT's
# discard. P1 plays it and returns Open Fire from P2's discard → it lands in P2's hand (not P1's).

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;theirDiscardCardIds:SOR_172}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P2HANDCOUNT:1
P2DISCARDCOUNT:0
