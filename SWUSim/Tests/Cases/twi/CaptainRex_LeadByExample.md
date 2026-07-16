# WhenPlayed_Create2Clones
#// TWI_097 Captain Rex (Unit 4/4, Ground, cost 6, Command/Heroism) — "When Played: Create 2 Clone
#// Trooper tokens." Rex enters at ground index 0, then creates 2 Clone Trooper (TWI_T02) tokens at
#// indices 1,2. Base g = Command + leader gw = Heroism cover both pips → no penalty.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:TWI_097}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TWI_097
P1GROUNDARENAUNIT:1:CARDID:TWI_T02
P1GROUNDARENAUNIT:2:CARDID:TWI_T02
