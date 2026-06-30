# IBH_005 I'll Cover For You — with no enemy units, the event fizzles cleanly (plays to discard, no
#   decision, no crash).

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_005

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
