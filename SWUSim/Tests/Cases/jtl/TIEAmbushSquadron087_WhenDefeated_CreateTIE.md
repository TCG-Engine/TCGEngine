# JTL_087 TIE Ambush Squadron — When Defeated: Create a TIE Fighter token. The pre-damaged squadron dies
# attacking SOR_044 and leaves a TIE Fighter behind.

## GIVEN
P1LeaderBase: JTL_005/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:1
