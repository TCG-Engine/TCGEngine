# IBH_005 I'll Cover For You — with only ONE enemy unit, it takes 1 damage and the "another enemy unit"
#   half fizzles cleanly (single mandatory target auto-resolves, no leftover decision).

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_005
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION
