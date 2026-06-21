# LOF_125 The Burden of Masters — Put a Force unit from discard on the bottom of your deck. If you do, play
# a unit from your hand and give it 2 Experience tokens. P1 banks LOF_050 from discard, then plays SOR_059
# (1/3) which enters with 2 Experience → 3/5.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:LOF_125,SOR_059;discardCardIds:LOF_050}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_059
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5
