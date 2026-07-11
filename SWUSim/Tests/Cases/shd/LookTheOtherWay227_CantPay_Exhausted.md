# SHD_227 Look the Other Way — with only 1 ready resource, P2 cannot pay the 2, so SOR_046 is exhausted
# (no choice is offered).

## GIVEN
CommonSetup: yyk/yyk/{theirResources:1}
WithActivePlayer: 1
WithP1Hand: SHD_227
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
