# Credit token core — defeating the token is optional. P1 declines (AnswerDecision:-), pays the full
#   2-resource cost, and keeps the Credit token.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Credits: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1CREDITCOUNT:1
P1RESAVAILABLE:0
P1NODECISION
