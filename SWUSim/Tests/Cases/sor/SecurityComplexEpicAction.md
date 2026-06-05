## GIVEN
P1LeaderBase: SOR_014/SOR_019
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1BASE:EPICUSED
