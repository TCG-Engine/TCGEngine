# WhenPlayed_ExhaustGround
#// LOF_207 Loth-Cat — When Played: may exhaust a ground unit. P1 plays it and exhausts the enemy 3/7.

## GIVEN
CommonSetup: yyk/ggw/{myResources:2;handCardIds:LOF_207}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
