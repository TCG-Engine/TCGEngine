# TWI_246 Tranquility (Unit 7/6, Space, cost 7, Heroism, Republic/Vehicle/Capital Ship) — "When Played:
# You may return a Republic unit from your discard pile to your hand." Returns the Republic unit TWI_109.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7;handCardIds:TWI_246;discardCardIds:TWI_109}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_246
P1HANDCOUNT:1
P1DISCARDCOUNT:0
