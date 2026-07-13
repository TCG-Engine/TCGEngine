# TWI_089 Consolidation of Power — a hand unit is only playable if its cost <= combined power of the
# chosen units. Choosing only SOR_140 (power 2) makes the budget 2, so SOR_046 (cost 4) is NOT eligible:
# no play is offered and the chosen unit is simply defeated.
## GIVEN
CommonSetup: ggk/bbw/{myResources:6;handCardIds:TWI_089,SOR_046}
P1OnlyActions: true
WithP1GroundArena: SOR_140:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1NODECISION
