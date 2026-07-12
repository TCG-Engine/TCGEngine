# TWI_080 Poggle the Lesser — declining (NO) leaves Poggle ready and creates no token.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SEC_080}
P1OnlyActions: true
WithP1GroundArena: TWI_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_080
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENACOUNT:2
