# SOR_183 Bounty Hunter Crew — "When Played: You may return an event from a discard pile to its
# owner's hand." P1 plays it (Ambush + WhenPlayed both fire); the WhenPlayed returns Open Fire from
# P1's OWN discard to P1's hand. (P2 has no units so Ambush has no target.)

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;discardCardIds:SOR_172}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:0
