# DamageDraw
#// LOF_141 Death Field — Deal 2 damage to each non-Vehicle enemy unit. If you control a Force unit, draw a
#// card. Both enemy units take 2; P1 controls Plo Koon (Force) so draws SOR_059 into hand.

## GIVEN
CommonSetup: rrk/ggw/{myResources:4;handCardIds:LOF_141}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1Deck: SOR_059
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2
P1HANDCOUNT:1
