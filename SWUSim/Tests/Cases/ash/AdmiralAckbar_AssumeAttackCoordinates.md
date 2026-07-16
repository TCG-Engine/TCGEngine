# SelfDefeatPlaySpaceUnits
#// ASH_110 Admiral Ackbar (Ground, 6/6, cost 5) — When Played: you may defeat this unit; if you do, search
#// the top 10 cards of your deck for any number of space units with combined cost 5 or less and play each
#// for free. P1 defeats Ackbar, then plays SOR_225 (cost 2) and SOR_237 (cost 2) from the deck for free.
## GIVEN
CommonSetup: ggw/ggk/{myResources:5;handCardIds:ASH_110}
WithP1Deck: [SOR_225 SOR_237]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_225,SOR_237
## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:2
