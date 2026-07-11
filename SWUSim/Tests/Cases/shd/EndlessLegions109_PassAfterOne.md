# SHD_109 Endless Legions — play one unit, then pass: the loop stops there (your clarification), leaving
# the second unit-resource in play as a resource. One unit enters; resource count drops by exactly 1.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Hand: SHD_109
WithP1Resources: 1:SEC_080:0,1:SOR_128:0,14:SOR_251:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1RESCOUNT:15
