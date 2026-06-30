# SEC_074 Relief Request — decline the optional disclose → only the first heal happens.
# idx0 healed to 0; idx1 keeps its 3 damage (the second heal is never offered).

## GIVEN
CommonSetup: bbk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SOR_046:1:3
WithP1Hand: SEC_074
WithP1Hand: SEC_059

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:3
P1HANDCOUNT:1
P1NODECISION
