# SHD_078 Fell the Dragon — with no non-leader unit at 5+ power (only a 3-power SOR_128), the effect has
# no valid target and fizzles cleanly (no decision). The event still lands in the discard.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_078
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:1
P1NODECISION
