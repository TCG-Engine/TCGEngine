# HiddenExp
#// LOF_241 In the Shadows — Give an Experience token to each of up to 3 friendly units with Hidden. Both
#// Hidden LOF_132 units (3/4) are chosen and become 4/5.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_241}
P1OnlyActions: true
WithP1GroundArena: LOF_132:1:0
WithP1GroundArena: LOF_132:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:5
