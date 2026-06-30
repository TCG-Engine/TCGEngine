# SOR_222 Waylay — can also bounce your own unit back to hand

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
