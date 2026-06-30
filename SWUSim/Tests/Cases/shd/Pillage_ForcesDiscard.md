## GIVEN
CommonSetup: grw/grw/{myResources:4;handCardIds:SHD_181;theirHandCardIds:SHD_135,SOR_095}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0

## EXPECT
P2DISCARDCOUNT:2
P1HANDCOUNT:0
P1DISCARDCOUNT:1
