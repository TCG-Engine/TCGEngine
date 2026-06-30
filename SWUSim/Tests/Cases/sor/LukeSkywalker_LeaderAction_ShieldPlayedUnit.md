# SOR_005 Luke Skywalker — Leader Action: Shield a Heroism unit played this phase.

## GIVEN
CommonSetup: gbw/grw/{myResources:3;handCardIds:SOR_095}
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
