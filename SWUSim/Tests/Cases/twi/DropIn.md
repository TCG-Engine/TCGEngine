# Create2Clones
#// TWI_251 Drop In (Event, cost 4, Heroism) — "Create 2 Clone Trooper tokens."

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:TWI_251}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P1GROUNDARENAUNIT:1:CARDID:TWI_T02
