## GIVEN
# SHD_181 Pillage: Aggression aspect, cost 4
# SOR_014 (Sabine) provides Aggression — no penalty, plays at 4
# SHD_135 Kylo's TIE Silencer: Villainy+Aggression; covered by SOR_010 (Darth Vader)
CommonSetup: grw/grk/{myResources:4;handCardIds:SHD_181;theirHandCardIds:SHD_135,SOR_095}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0

## EXPECT
P2DISCARDUNIT:0:CARDID:SHD_135
P2DISCARDUNIT:0:MODIFIER:TPP
