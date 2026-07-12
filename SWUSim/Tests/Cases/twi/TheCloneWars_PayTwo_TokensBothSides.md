# TWI_125 The Clone Wars (Event, cost 2, Command) — "Pay any number of resources. Create that many
# Clone Trooper tokens. Each opponent creates that many Battle Droid tokens." Pay 2 → the caster gets
# 2 Clone Troopers, and the opponent creates 2 Battle Droids. Cost 2 + pay 2 → needs 4 ready resources.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:TWI_125}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:2

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:TWI_T01
P1RESAVAILABLE:0
