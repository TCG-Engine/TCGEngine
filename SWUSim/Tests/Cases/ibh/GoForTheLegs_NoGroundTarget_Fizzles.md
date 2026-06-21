# IBH_018 Go for the Legs — with only an enemy SPACE unit (no ground), there is no valid target and the
#   event fizzles cleanly (space unit stays ready, no decision).

## GIVEN
CommonSetup: yyk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: IBH_018
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENAUNIT:0:READY
P1NODECISION
