# LOF_033 Nameless Terror — When Played: You may exhaust a Force unit. P1 plays it and exhausts Plo Koon.
# (The On Attack "enemy units lose the Force trait" half is deferred — trait suppression infra.)

## GIVEN
CommonSetup: bbk/ggw/{myResources:3;handCardIds:LOF_033}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
