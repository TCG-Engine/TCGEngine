# PlayUnit_ExhaustSearchUnit
#// TWI_101 Mas Amedda (Unit 0/4, Ground, cost 2, Command/Command, Republic/Official) — "When you play
#// another unit: You may exhaust this unit. If you do, search the top 4 cards of your deck for a unit,
#// reveal it, and draw it." Playing JTL_069 lets P1 exhaust Mas Amedda and draw the lone unit (SOR_046)
#// from the top 4 (the rest are events).

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SEC_080}
P1OnlyActions: true
WithP1GroundArena: TWI_101:1:0
WithP1Deck: [SOR_046 TWI_175 TWI_175 TWI_175]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_046

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_101
P1GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1
P1DECKCOUNT:3
