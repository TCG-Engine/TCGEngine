# ASH_094 Moff Jerjerrod — the doubling is a "may". P1 plays SEC_191 (create 2 Spy) with Jerjerrod in
# play and DECLINES: only 2 Spies are created and Jerjerrod survives. Final P1 ground = Jerjerrod + SEC_191
# + 2 Spy = 4.
## GIVEN
CommonSetup: yyk/yyk/{myResources:5;handCardIds:SEC_191}
WithActivePlayer: 1
WithP1GroundArena: ASH_094:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:4
