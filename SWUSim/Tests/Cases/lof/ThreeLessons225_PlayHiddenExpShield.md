# LOF_225 Three Lessons — Play a unit from your hand; it gains Hidden for this phase and gets an Experience
# token and a Shield token. Plo Koon enters as 7/9 (one Experience) with Hidden and a Shield.

## GIVEN
CommonSetup: yyw/ggk/{myResources:10;handCardIds:LOF_225,LOF_050}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:9
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden
