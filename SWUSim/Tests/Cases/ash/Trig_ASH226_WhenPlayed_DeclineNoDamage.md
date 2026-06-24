# ASH_226 Qi'ra — declining the When Played discard means no damage is dealt. P1 plays Qi'ra and declines
# the optional discard, so SEC_080 survives and the spare hand card is kept.
## GIVEN
CommonSetup: yyk/yyk/{myResources:7;handCardIds:ASH_226,SOR_095}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:1
P1HANDCOUNT:1
