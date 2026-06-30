# LOF_075 Cure Wounds — "Use the Force. If you do, heal 6 damage from a unit." With the Force, P1 plays
# it (mandatory use) and heals 6 damage from its 3/7 (6 damage → 0).

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;handCardIds:LOF_075}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_046:1:6

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:DAMAGE:0
