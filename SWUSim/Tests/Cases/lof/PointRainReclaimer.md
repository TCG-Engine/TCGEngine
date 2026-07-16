# Jedi_GivesExp
#// LOF_092 Point Rain Reclaimer — When Played: if you control a Jedi unit, may give an Experience token to
#// this unit. P1 controls Plo Koon (Jedi), plays the Reclaimer, and accepts the Experience token.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_092}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
