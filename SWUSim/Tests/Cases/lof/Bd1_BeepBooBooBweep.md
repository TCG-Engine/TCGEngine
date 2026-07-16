# 191_AuraBuff
#// LOF_191 BD-1 — When Played: Choose another friendly unit; while BD-1 is in play, the chosen unit gets
#// +1/+0 and gains Saboteur. P1 plays BD-1 and chooses Plo Koon, who becomes 7/8 with Saboteur.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_191}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
