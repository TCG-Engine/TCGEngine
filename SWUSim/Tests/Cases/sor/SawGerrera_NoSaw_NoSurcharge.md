# SOR_153 Saw Gerrera — control: without Saw in play, an opponent's event carries no base surcharge.

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:0
WithP2Hand: SOR_251
WithP2Resources: 1

## WHEN
- P2>PlayHand:0

## EXPECT
P2BASEDMG:0
P2DISCARDCOUNT:1
