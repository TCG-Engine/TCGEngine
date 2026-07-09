# SHD_161 Stolen Landspeeder — "When Played: If you played this unit from your hand, an opponent
# takes control of it." P1 plays it from hand (1 resource, Aggression covered by the rw leader);
# it moves to P2's ground arena, still exhausted from entering play.

## GIVEN
CommonSetup: grw/grw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_161

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_161
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
