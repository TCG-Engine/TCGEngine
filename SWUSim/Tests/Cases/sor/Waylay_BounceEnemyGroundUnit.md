# SOR_222 Waylay — bounce an enemy ground unit back to its owner's hand

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
