# UseForce_ExhaustEnemyDraw
#// LOF_218 Impossible Escape — "You may either exhaust a friendly unit OR use the Force. If you do either,
#// exhaust an enemy unit and draw a card." P1 pays via the Force, then exhausts the enemy unit and draws.

## GIVEN
CommonSetup: yyw/rrk/{myResources:1;handCardIds:LOF_218}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:UseForce

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:1
