# ForceUnitToTop
#// LOF_103 Following the Path — Search the top 8 for up to 2 Force units, put them on top of the deck; the
#// rest go to the bottom. Deck top is an event (LOF_077) with Plo Koon (Force) beneath; choosing Plo Koon
#// moves him to the top.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_103}
P1OnlyActions: true
WithP1Deck: LOF_077
WithP1Deck: LOF_050

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LOF_050

## EXPECT
P1DECKTOPCARD:LOF_050
