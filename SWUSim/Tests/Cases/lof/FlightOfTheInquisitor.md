# ReturnTwo
#// LOF_240 Flight of the Inquisitor — You may return a Force unit and a Lightsaber upgrade from your discard
#// to your hand. P1 returns LOF_050 (Force unit) and SOR_053 (Lightsaber upgrade); only the LOF_240 event
#// remains in discard.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_240;discardCardIds:LOF_050,SOR_053}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:1
