# WhenPlayedEitherExpOrExhaust
#// LAW_067 Jyn Erso (2/2) — When Played: either give an Experience token to a unit OR exhaust a unit.
#// Choose Exhaust; exhaust the enemy SOR_046.

## GIVEN
CommonSetup: gyw/bgw/{myResources:2}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_067

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Exhaust
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
