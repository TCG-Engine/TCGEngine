# LOF_035 Talzin's Assassin — without the Force the optional "use the Force" is not offered (you can't
# use a Force you don't control): the unit just enters play and no debuff happens.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4;handCardIds:LOF_035}
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:4
P1NODECISION
