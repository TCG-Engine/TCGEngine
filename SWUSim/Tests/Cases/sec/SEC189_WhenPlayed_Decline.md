# SEC_189 — the exhaust is a "may". Declining leaves the enemy SOR_046 ready.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:READY
