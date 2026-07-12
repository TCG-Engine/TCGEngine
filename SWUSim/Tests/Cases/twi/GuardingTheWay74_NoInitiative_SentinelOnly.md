# TWI_074 Guarding the Way — WITHOUT the initiative (P1OnlyActions gives P2 the initiative), the chosen
# unit gains Sentinel but no +2/+2 (power stays 3).

## GIVEN
CommonSetup: bbw/grw/{myResources:2;handCardIds:TWI_074}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:POWER:3
