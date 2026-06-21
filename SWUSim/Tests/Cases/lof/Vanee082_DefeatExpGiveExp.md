# LOF_082 Vaneé — When Played: may defeat an Experience token on a friendly unit. If you do, give an
# Experience token to a friendly unit. P1 defeats the Experience on SOR_095 and moves it to SOR_046.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_082}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
