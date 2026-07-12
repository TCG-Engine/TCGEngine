# TWI_084 Kraken (Unit 2/5, Ground, cost 5, Command/Villainy) — "When Played: Create 2 Battle Droid
# tokens." Kraken enters at ground index 0, then its When Played creates 2 Battle Droid (TWI_T01)
# tokens at indices 1,2. Base g = Command + leader yk = Villainy cover both pips → no penalty.

## GIVEN
CommonSetup: gyk/grw/{myResources:5;handCardIds:TWI_084}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TWI_084
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1GROUNDARENAUNIT:2:CARDID:TWI_T01
