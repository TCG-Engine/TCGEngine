# SHD_050 Chewbacca — declining the optional defeat leaves the board untouched.

## GIVEN
CommonSetup: bbw/bbw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_050
WithP2GroundArena: LAW_124:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
