# SHD_107 Enterprising Lackeys — declining the "may" leaves it in the discard and the resources
# untouched.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SHD_107:1:1
WithP1Resources: 2:SOR_046:1
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P1RESCOUNT:2
P1RESAVAILABLE:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_107
