# LOF_075 Cure Wounds — without the Force the event fizzles (you can't use a Force you don't control), so
# no healing happens (damage stays 6).

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;handCardIds:LOF_075}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:6

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:6
P1NODECISION
