# TWI_171 Grenade Strike — the second hit is optional: declining (AnswerDecision:-) leaves the second
# unit undamaged.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_171}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:0
