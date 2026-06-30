## GIVEN
CommonSetup: grw/ggk/{myResources:1;handCardIds:ASH_259}
WithP2GroundArena: SOR_229:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION
