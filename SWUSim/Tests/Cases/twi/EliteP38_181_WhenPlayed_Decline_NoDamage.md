# TWI_181 Elite P-38 Starfighter — the "may" is optional: declining (AnswerDecision:-) leaves the enemy
# unit undamaged.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3;handCardIds:TWI_181}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_181
P2GROUNDARENAUNIT:0:DAMAGE:0
