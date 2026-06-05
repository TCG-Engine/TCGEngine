# SOR_089 Relentless (8/8) — "The first event played by each opponent each round loses all abilities."
# P1 controls Relentless; P2 plays Confiscate (its first event of the round) targeting P1's only upgrade.
# The event is blanked, so the upgrade (SOR_120 on SEC_080) is NOT defeated.

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_089:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Hand: SOR_251
WithP2Resources: 1

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:1
