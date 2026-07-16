# CreatesCreditToken
#// LAW_244 Unmarked Credits (Event, cost 1, Cunning) — Create a Credit token.
#//   The token is created in the resource zone but is NOT a resource (RESCOUNT unchanged).

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: LAW_244

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:1
P1RESCOUNT:2
P1RESAVAILABLE:1
P1NODECISION
