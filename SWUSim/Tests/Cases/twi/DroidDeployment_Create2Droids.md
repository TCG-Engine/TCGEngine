# TWI_237 Droid Deployment (Event, cost 2, Villainy) — "Create 2 Battle Droid tokens."

## GIVEN
CommonSetup: gyk/grw/{myResources:2;handCardIds:TWI_237}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
