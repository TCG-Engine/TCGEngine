## GIVEN
CommonSetup: gyw/gyw/{myResources:1;handCardIds:SOR_217}
WithP1GroundArena: SOR_095
WithP2GroundArena: SOR_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0
