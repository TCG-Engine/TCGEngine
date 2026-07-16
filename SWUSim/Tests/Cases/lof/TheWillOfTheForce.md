# BounceAndDiscard
#// LOF_227 The Will of the Force — "Return a non-leader unit to its owner's hand. You may use the Force.
#// If you do, that player discards a random card." P1 bounces the enemy 3/7 (P2's only unit) to P2's hand,
#// then uses the Force; P2 now has exactly that one card and discards it at random.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;handCardIds:LOF_227}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P2GROUNDARENACOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:1
