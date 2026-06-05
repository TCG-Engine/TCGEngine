# JTL_096 Blue Leader — Ambush + "When Played: You may pay 2 resources. If you do, move this unit to
# the ground arena and give 2 Experience tokens to it." Played into an empty enemy board (Ambush has
# no target → only the WhenPlayed fires); P1 pays 2 and Blue Leader moves to the ground arena as a 5/5
# (3/3 base + 2 Experience).

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_096}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
