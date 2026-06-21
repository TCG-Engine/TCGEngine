# IBH_021 Improvised Detonation — with no READY friendly unit (only an exhausted one), there is no unit
#   to attack with and the event fizzles cleanly.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_021
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
