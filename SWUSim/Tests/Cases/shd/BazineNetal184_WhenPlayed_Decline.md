# SHD_184 Bazine Netal — declining the optional discard: opponent's hand, discard, and deck are
# untouched (and no draw happens).

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;theirhandCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP1Hand: SHD_184
WithP2Deck: SOR_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P2DECKCOUNT:1
