# ASH_038 Purrgil Ultra — declining the optional return leaves the board untouched (no return, no damage).
## GIVEN
CommonSetup: gyk/gyk/{myResources:8;handCardIds:ASH_038}
WithP1GroundArena: SEC_135:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
