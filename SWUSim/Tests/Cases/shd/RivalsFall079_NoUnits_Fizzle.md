# SHD_079 Rival's Fall — with no units in play the defeat has no target and fizzles cleanly; the event
# still lands in the discard.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_079

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1NODECISION
