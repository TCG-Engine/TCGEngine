# JTL_232 Jump to Lightspeed (event) — Return a friendly space unit and its non-leader upgrades to
# owners' hands. SOR_237 (carrying SOR_120) and SOR_120 both return to P1's hand. (Free-replay rider
# deferred.)

## GIVEN
P1LeaderBase: JTL_016/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_232
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:2
