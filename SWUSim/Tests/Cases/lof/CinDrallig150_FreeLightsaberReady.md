# LOF_150 Cin Drallig — When Played: you may play a Lightsaber upgrade from hand for free on him; if you do,
# ready him. P1 plays Cin Drallig (5/6), attaches SOR_054 (Jedi Lightsaber +3/+3) for free → 8/9, and he is
# readied (he entered exhausted from the play).

## GIVEN
CommonSetup: rrw/ggk/{myResources:8;handCardIds:LOF_150,SOR_054}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_150
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HP:9
P1GROUNDARENAUNIT:0:READY
