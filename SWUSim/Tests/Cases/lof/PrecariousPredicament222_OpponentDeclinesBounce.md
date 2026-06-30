# LOF_222 A Precarious Predicament — Return an enemy non-leader unit unless its controller says "It could
# be worse." P1 targets SOR_046; P2 declines (does not object), so SOR_046 is returned to P2's hand.

## GIVEN
CommonSetup: yyk/ggw/{myResources:2;handCardIds:LOF_222}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
