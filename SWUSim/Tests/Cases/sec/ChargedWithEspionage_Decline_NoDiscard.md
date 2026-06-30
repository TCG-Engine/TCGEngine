# SEC_230 Charged with Espionage — decline the disclose → opponent's hand untouched.

## GIVEN
CommonSetup: yyk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_230
WithP1Hand: SEC_220
WithP1Hand: SEC_233
WithP2Hand: SOR_095
WithP2Hand: SEC_074

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P1NODECISION
