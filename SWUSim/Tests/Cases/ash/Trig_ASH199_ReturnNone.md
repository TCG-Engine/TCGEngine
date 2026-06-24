# ASH_199 There Is No Conflict — "any number" includes zero. Declining the multi-select returns no
# upgrades, so SOR_095 keeps SOR_120 and ends at 3 + SOR_120(+2) + ASH_199(+2) = 7 power (nothing in hand).
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_199}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1HANDCOUNT:0
