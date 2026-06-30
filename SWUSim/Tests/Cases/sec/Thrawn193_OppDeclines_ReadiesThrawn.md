# SEC_193 Grand Admiral Thrawn — if the opponent declines, ready Thrawn (he enters exhausted, then readies).

## GIVEN
CommonSetup: yyk/grw/{myResources:7}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_193

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_193
P1GROUNDARENAUNIT:0:READY
P1NODECISION
