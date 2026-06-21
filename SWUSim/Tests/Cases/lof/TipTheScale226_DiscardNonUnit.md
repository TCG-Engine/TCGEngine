# LOF_226 Tip the Scale — Look at an opponent's hand and discard a non-unit card from it. P2's hand has a
# unit (SOR_146) and an event (SOR_073); the event is discarded.

## GIVEN
CommonSetup: yyk/ggw/{myResources:2;handCardIds:LOF_226}
P1OnlyActions: true
WithP2Hand: SOR_146
WithP2Hand: SOR_073

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2DISCARDCOUNT:1
