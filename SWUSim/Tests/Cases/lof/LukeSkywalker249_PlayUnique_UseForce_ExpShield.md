# LOF_249 Luke Skywalker (3/5) — "When you play another unique unit: may use the Force → give an
# Experience and a Shield token to this unit." P1 plays the unique Owen Lars (LOF_057); the reaction lets
# P1 use the Force, and Luke gains an Experience + a Shield (2 subcards).

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;handCardIds:LOF_057}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_249:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
