# SOR_186 No Good to Me Dead — you may target an ALREADY-exhausted unit just to stop it readying.
# SOR_046 starts exhausted; the exhaust is a no-op but the can't-ready flag still applies, so it stays
# EXHAUSTED through regroup while the control SEC_080 readies.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SOR_186}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:0:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
