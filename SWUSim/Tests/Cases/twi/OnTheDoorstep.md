# Create3ReadyDroids
#// TWI_190 On the Doorstep (Event, cost 4, Cunning/Villainy) — "Create 3 Battle Droid tokens and ready
#// them." Tokens normally enter exhausted; this creates 3 Battle Droids (TWI_T01) that are READY.
#// Base y = Cunning + leader yk = Cunning/Villainy cover both pips → no penalty.

## GIVEN
CommonSetup: yyk/grw/{myResources:4;handCardIds:TWI_190}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:2:READY
