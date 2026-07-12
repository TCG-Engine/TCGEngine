# TWI_102 Manufactured Soldiers (Event, cost 3, Command/Command) — "Choose one: Create 2 Clone
# Trooper tokens. / Create 3 Battle Droid tokens." Choosing the Clones mode creates 2 Clone Trooper
# (TWI_T02) tokens. Base g + leader gw both Command cover the double Command pip → no penalty.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:TWI_102}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Clones

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P1GROUNDARENAUNIT:1:CARDID:TWI_T02
