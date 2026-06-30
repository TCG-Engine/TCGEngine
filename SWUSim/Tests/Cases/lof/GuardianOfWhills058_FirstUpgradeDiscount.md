# LOF_058 Guardian of the Whills — "The first upgrade you play on this unit each round costs 1 resource
# less." P1 plays Resilient (cost 1) onto the Guardian for 0, leaving its 1 resource unspent.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;handCardIds:SOR_069}
P1OnlyActions: true
WithP1GroundArena: LOF_058:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:1
