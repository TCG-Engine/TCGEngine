# LOF_255 Curious Flock (1/1) — When Played: Pay up to 6 resources. For each resource paid, give an
# Experience token to this unit. P1 pays 2 (then declines), so the Flock becomes 3/3 and 3 resources are
# spent total (1 to play + 2 paid).

## GIVEN
CommonSetup: bbw/ggk/{myResources:7;handCardIds:LOF_255}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1RESAVAILABLE:4
