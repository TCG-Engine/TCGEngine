## GIVEN
CommonSetup: ygw/byk
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:3
WithP2GroundArenaUpgrade: 0:SOR_120
WithP1Hand: SOR_251
WithP1Resources: 1

## WHEN
P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:2