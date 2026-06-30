# SEC_141 The Galleon — decline the disclose → no Spy tokens created.

## GIVEN
CommonSetup: rrk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SEC_141
WithP1Hand: SEC_133
WithP1Hand: SEC_133

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1NODECISION
