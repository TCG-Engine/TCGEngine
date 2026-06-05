# SOR_183 Bounty Hunter Crew — played WITH an enemy unit on board, so BOTH entry triggers fire
# (Ambush + WhenPlayed) → the trigger-order MZCHOOSE appears. Resolving the WhenPlayed first returns
# Open Fire from P1's discard to hand; the Ambush is then declined (the enemy unit is untouched).

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:SOR_183;discardCardIds:SOR_172}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
