# LAW_248 Windfall (Event, cost 5, Cunning) — Create 3 Credit tokens.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: LAW_248

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:3
P1RESCOUNT:5
P1NODECISION
