# PlayCheapUnitsFromDiscard
#// ASH_104 Dathomiri Magicks (Event, cost 6) — Play up to 3 non-Vehicle units that each cost 2 or less
#// from your discard pile for free. P1's discard has SEC_080 (cost 2) and SOR_128 (cost 1); both are played
#// for free into the ground arena.
## GIVEN
CommonSetup: ggk/ggk/{myResources:6;handCardIds:ASH_104;discardCardIds:SEC_080,SOR_128}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1
## EXPECT
P1GROUNDARENACOUNT:2
