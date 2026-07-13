# TWI_040 A Fine Addition — the play is a "may": P1 declines (answers "-"), so nothing is played, the
# upgrade stays in hand, and the unit stays vanilla.
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1HANDCOUNT:1
