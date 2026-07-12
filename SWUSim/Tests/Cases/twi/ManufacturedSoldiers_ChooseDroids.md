# TWI_102 Manufactured Soldiers — the Droids mode creates 3 Battle Droid (TWI_T01) tokens.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:TWI_102}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Droids

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:2:CARDID:TWI_T01
