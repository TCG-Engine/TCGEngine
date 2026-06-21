# LOF_070 Anakin Skywalker — two When-Played windows: a Heroism card AND a Villainy card are in P1's
# discard, so both -3/-3 effects fire. P1 debuffs the enemy 3/7 twice → power 0, remaining HP 1.

## GIVEN
CommonSetup: bbk/ggw/{myResources:6;handCardIds:LOF_070;discardCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:HP:4
P2GROUNDARENAUNIT:1:HP:4
