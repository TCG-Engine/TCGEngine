# SHD_227 Look the Other Way — P2 can afford the 2 but declines to pay, so SOR_046 is exhausted and P2
# keeps its 2 resources.

## GIVEN
CommonSetup: yyk/yyk/{theirResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_227
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
P2RESAVAILABLE:2
