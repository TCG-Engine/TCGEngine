# SHD_005 Hondo Ohnaka (front) — declining the "may" leaves Hondo ready and grants no Experience token.

## GIVEN
CommonSetup: ggk/ggk/{myLeader:SHD_005}
P1OnlyActions: true
WithP1Resources: 1:SHD_065:0,10:SOR_095:1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:-

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3
