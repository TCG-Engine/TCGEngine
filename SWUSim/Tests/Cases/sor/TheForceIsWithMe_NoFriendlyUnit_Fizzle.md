# SOR_055 The Force Is With Me — with no friendly unit to choose, the event fizzles cleanly: no
# decision is offered and it simply resolves to the discard pile.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_055

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P2BASEDMG:0
