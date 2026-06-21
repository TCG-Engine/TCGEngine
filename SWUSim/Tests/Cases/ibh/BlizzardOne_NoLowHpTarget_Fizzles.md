# IBH_099 Blizzard One — with no eligible low-HP ground unit (only a 4/7 wall), the may-defeat presents
#   no target and the play resolves with the unit simply in play.

## GIVEN
CommonSetup: bbk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: IBH_099
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IBH_099
P2GROUNDARENACOUNT:1
P1NODECISION
