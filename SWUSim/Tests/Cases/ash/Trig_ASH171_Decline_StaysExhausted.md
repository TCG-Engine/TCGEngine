# ASH_171 Pegasus Tri-Wing — declining the optional upgrade defeat means the Pegasus is NOT readied (it
# stays exhausted from being just played) and SOR_095 keeps SOR_120 (5 power).
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_171}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1SPACEARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:5
