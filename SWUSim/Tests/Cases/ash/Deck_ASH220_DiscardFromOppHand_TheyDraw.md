# ASH_220 Remnant Lookouts (Ground, 3/3, cost 3) — When Played: look at an opponent's hand; you may
# discard a card from it; if you do, they draw a card. P1 plays it, sees P2's one card (SOR_095) and
# discards it; P2 then draws back to 1 card and has 1 card in its discard pile.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_220;theirHandCardIds:SOR_095}
WithP2Deck: [SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
## EXPECT
P2DISCARDCOUNT:1
P2HANDCOUNT:1
