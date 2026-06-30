# ASH_220 Remnant Lookouts — declining the optional discard leaves the opponent's hand untouched (no
# discard, so they do not draw). P1 plays it, looks at P2's hand, and declines.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_220;theirHandCardIds:SOR_095}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2DISCARDCOUNT:0
P2HANDCOUNT:1
