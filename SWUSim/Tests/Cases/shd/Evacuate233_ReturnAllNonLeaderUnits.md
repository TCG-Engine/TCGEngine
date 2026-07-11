# SHD_233 Evacuate (6-cost event, Cunning) — "Return each non-leader unit to its owner's hand." All units
# on both sides are bounced: P1's SOR_095 to P1's hand; P2's SOR_046 and SOR_128 to P2's hand.

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_233
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P2HANDCOUNT:2
