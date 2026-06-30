## GIVEN
CommonSetup: gyw/gyw/{myResources:1;handCardIds:SOR_217}
WithP1GroundArena: SOR_207:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:0
