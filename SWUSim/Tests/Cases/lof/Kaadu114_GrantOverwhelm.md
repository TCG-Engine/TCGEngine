# LOF_114 Kaadu — When Played: may give another friendly unit Overwhelm for this phase. P1 grants
# Overwhelm to its SOR_095.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:LOF_114}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
