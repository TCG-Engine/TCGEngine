# SOR_222 Waylay — can target space arena units

## GIVEN
CommonSetup: ybk/grw/{myResources:3;handCardIds:SOR_222}
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirSpaceUnit:0

## EXPECT
P2SPACEARENACOUNT:0
P2HANDCOUNT:1
