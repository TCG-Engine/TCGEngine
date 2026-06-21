# LOF_263 Last Words — If a friendly unit was defeated this phase, give 2 Experience tokens to a unit. P1
# first plays LOF_264 to defeat its own SOR_059 (setting the "friendly defeated" flag), then LOF_263 gives
# Plo Koon 2 Experience → 8/10.

## GIVEN
CommonSetup: ggk/rrw/{myResources:9;handCardIds:LOF_264,LOF_263}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HP:10
