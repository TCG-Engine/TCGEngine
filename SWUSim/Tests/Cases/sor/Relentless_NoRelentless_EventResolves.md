# SOR_089 Relentless — control: without Relentless, P2's Confiscate resolves normally and defeats P1's
# upgrade.

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Hand: SOR_251
WithP2Resources: 1

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:1
