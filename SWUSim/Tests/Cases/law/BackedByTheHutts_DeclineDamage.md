# LAW_247 Backed by the Hutts — the damage is optional ("You may"). P1 declines it; the credit is still
#   created. (Credit-payment offer declined first, then the damage MZMAYCHOOSE declined.)

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_247
WithP1Credits: 2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:-

## EXPECT
P1CREDITCOUNT:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
