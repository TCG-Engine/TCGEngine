# LOF_126 Overpower — Give a unit +3/+3 and Overwhelm for this phase. SOR_046 (3/7) becomes 6/10 with
# Overwhelm.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:LOF_126}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:10
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
