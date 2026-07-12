# TWI_197 Republic Attack Pod — with only 2 units, no discount applies; cost 6 can't be paid with 5
# resources, so the Pod stays in hand.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5;handCardIds:TWI_197}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:1
