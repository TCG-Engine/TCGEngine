# LAW_227 Rookie Rocket-jumper (Cunning, cost 1) — When Played: you may pay 1 resource. If you do, give
# a Shield token to this unit. Pay 1 -> self-shield.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Hand: LAW_227

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_227
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1RESAVAILABLE:0
