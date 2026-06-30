# IBH_104 The Desolation of Hoth — with only a cost-8 enemy (no unit costing 3 or less), there is no
#   eligible target and the event fizzles cleanly.

## GIVEN
CommonSetup: bbk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: IBH_104
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P1NODECISION
