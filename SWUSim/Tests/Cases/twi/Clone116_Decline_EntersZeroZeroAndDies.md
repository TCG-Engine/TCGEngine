# TWI_116 Clone — the copy is optional ("You MAY"). If P1 declines the copy, Clone enters play as its
# printed self: a 0/0 unit, which is immediately defeated by the 0-HP state check. It goes to P1's
# discard as TWI_116. The enemy SOR_095 that could have been copied is untouched.
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDUNIT:0:CARDID:TWI_116
P2GROUNDARENACOUNT:1
