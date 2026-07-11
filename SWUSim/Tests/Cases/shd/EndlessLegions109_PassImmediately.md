# SHD_109 Endless Legions — passing on the first offer plays nothing (loop ends immediately).
# Same setup, but P1 declines the reveal: no units enter play, all resources remain.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Hand: SHD_109
WithP1Resources: 1:SEC_080:0,1:SOR_128:0,14:SOR_251:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:16
P1DISCARDCOUNT:1
