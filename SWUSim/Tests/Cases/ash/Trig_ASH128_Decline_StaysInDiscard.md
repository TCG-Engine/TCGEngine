# ASH_128 Bothan-5 — declining the optional capture leaves the defeated unit in the discard pile. SOR_095
# dies attacking SOR_046 and P1 declines, so SOR_095 stays in the discard.
## GIVEN
CommonSetup: ggk/ggk
WithP1SpaceArena: ASH_128:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
