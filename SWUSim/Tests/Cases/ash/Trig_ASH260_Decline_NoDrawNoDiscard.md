# ASH_260 Mos Espa Watermonger — declining the optional draw skips both the draw and the discard. P1 plays
# the Watermonger and declines, so its spare hand card (SOR_095) is kept and nothing is discarded.
## GIVEN
CommonSetup: bbw/bbk/{myResources:2;handCardIds:ASH_260,SOR_095}
WithP1Deck: [SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
