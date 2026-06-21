# SEC_192 Grand Moff Tarkin — fizzle guard: with no enemy non-leader Vehicle, the When Played takes
# nothing. P2's only unit is SEC_080 (Imperial, NOT a Vehicle), so Tarkin just enters play and SEC_080
# stays under P2's control.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SEC_192
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_192
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
