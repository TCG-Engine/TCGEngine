# SecurityComplexEpicAction
## GIVEN
CommonSetup: brw/grw/{
  myBase:SOR_019
}
SkipPreGame: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1BASE:EPICUSED
