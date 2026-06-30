# LOF_259 Ravening Gundark — When Played: deal 1 damage to a ground unit. P1 deals 1 to the enemy 3/7.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:LOF_259}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
