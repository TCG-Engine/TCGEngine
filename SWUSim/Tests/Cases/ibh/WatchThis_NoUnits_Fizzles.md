# IBH_052 Watch This — with no units in play, there is nothing to return and the event fizzles cleanly.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_052

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1NODECISION
