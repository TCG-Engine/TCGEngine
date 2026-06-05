# JTL_096 Blue Leader — the "may pay 2" is optional. Declining (AnswerDecision:NO) leaves Blue Leader
# in the space arena as a plain 3/3 with no Experience.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_096}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:JTL_096
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:POWER:3
