# ChooseSides_ExchangeControl
#// SHD_132 Choose Sides — "Choose a friendly non-leader unit and an enemy non-leader unit. Exchange
#// control of those units." P1 swaps its SOR_046 for P2's SHD_095: afterwards P1 controls SHD_095 and P2
#// controls SOR_046.

## GIVEN
CommonSetup: ggk/ggk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_132
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_095
P2GROUNDARENAUNIT:0:CARDID:SOR_046
