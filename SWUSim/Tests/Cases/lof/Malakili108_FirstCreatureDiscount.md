# LOF_108 Malakili — The first Creature unit you play each phase costs 1 resource less. With Malakili in
# play, P1 plays LOF_063 (a Creature, cost 3) for 2, leaving 1 resource.

## GIVEN
CommonSetup: bbw/ggk/{myResources:3;handCardIds:LOF_063}
P1OnlyActions: true
WithP1GroundArena: LOF_108:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:1
