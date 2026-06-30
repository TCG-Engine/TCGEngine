## GIVEN
CommonSetup: grw/grw/{myResources:3;handCardIds:SOR_172}
WithP2GroundArena: SOR_164

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
