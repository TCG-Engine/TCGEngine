# TWI_234 The Invisible Hand (Unit 4/7, Space, cost 8, Villainy) — "When Played: Create 4 Battle Droid
# tokens." Invisible Hand enters the space arena; its When Played creates 4 Battle Droids (Ground).
# Leader yk covers the Villainy pip → no penalty.

## GIVEN
CommonSetup: gyk/grw/{myResources:8;handCardIds:TWI_234}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:TWI_234
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
