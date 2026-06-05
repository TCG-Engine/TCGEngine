# JTL_230 Electromagnetic Pulse (event) — if the 2 damage defeats the unit, the exhaust is moot. The
# TIE (SOR_225, 2/1) is defeated outright by the 2 damage.

## GIVEN
P1LeaderBase: JTL_016/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_230
WithP1Resources: 1
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
