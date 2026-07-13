# TWI_089 Consolidation of Power — the free play is optional ("You may"), but the chosen units are
# defeated regardless. Choose both units, decline the play → both chosen units are defeated and SOR_046
# stays in hand.
## GIVEN
CommonSetup: ggk/bbw/{myResources:6;handCardIds:TWI_089,SOR_046}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
