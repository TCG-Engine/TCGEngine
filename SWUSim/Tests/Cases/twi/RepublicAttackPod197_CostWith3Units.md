# TWI_197 Republic Attack Pod (Unit 6/7, Ground, cost 6, Cunning/Heroism) — "If you control 3 or more
# units, this unit costs 1 resource less to play." With 3 units already in play, it costs 5; P1 has 5.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5;handCardIds:TWI_197}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0 SOR_095:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:4
P1RESAVAILABLE:0
