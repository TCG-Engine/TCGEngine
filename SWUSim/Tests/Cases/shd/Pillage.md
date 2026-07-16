# DiscardFromHandGetsTpp
## GIVEN
#// SHD_181 Pillage: Aggression aspect, cost 4
#// SOR_014 (Sabine) provides Aggression — no penalty, plays at 4
#// SHD_135 Kylo's TIE Silencer: Villainy+Aggression; covered by SOR_010 (Darth Vader)
CommonSetup: grw/grk/{myResources:4;handCardIds:SHD_181;theirHandCardIds:SHD_135,SOR_095}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0

## EXPECT
P2DISCARDUNIT:0:CARDID:SHD_135
P2DISCARDUNIT:0:MODIFIER:TPP

---

# PlayBackFromDiscard
## GIVEN
#// SHD_181 Pillage: Aggression aspect, cost 4; P1 covered by SOR_014 (Aggression)
#// SHD_135 Kylo's TIE Silencer: Villainy+Aggression, cost 2; P2 covered by SOR_010 (Aggression+Villainy)
CommonSetup: grw/grk/{myResources:4;theirResources:2;handCardIds:SHD_181;theirHandCardIds:SHD_135,SOR_095}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P1>Pass
- P2>PlayFromDiscard:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_135
P2DISCARDCOUNT:1
P2RESAVAILABLE:0

---

# AutoResolvesOnCard
## GIVEN
CommonSetup: grw/grw/{myResources:4;handCardIds:SHD_181;theirHandCardIds:SOR_095}

## WHEN
- P1>PlayHand:0

## EXPECT
P2DISCARDCOUNT:1
P2HANDCOUNT:0
P1DISCARDCOUNT:1

---

# ForcesDiscard
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
