# LOF_079 Shatterpoint — mode B: use the Force to defeat ANY non-leader unit (no HP limit). P1 picks
# ForceDefeat, uses the Force, and defeats the enemy 4/7.

## GIVEN
CommonSetup: bbk/ggw/{myResources:4;handCardIds:LOF_079}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:ForceDefeat

## EXPECT
P1NOFORCE
P2GROUNDARENACOUNT:0
