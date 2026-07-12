# SHD_090 Maul — declining the optional redirect leaves combat normal: Maul takes the 2 counter itself.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [SHD_090:1:0 LAW_124:1:0]
WithP2GroundArena: SOR_181:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_090
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:CARDID:LAW_124
P1GROUNDARENAUNIT:1:DAMAGE:0
