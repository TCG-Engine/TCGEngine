# JTL_127 Lightspeed Assault — with no enemy SPACE unit to damage, the event fizzles cleanly: the
# friendly space unit is NOT defeated and no indirect is dealt. (P2 has only a ground unit.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_127}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_069
P2BASEDMG:0
P1NODECISION
