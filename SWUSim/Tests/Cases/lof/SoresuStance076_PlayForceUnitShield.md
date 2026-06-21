# LOF_076 Soresu Stance — Play a Force unit from your hand (paying its cost) and give a Shield token to it.
# P1 plays the event, then plays Plo Koon (Force) from hand, who enters with a Shield.

## GIVEN
CommonSetup: bbw/ggk/{myResources:12;handCardIds:LOF_076,LOF_050}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
