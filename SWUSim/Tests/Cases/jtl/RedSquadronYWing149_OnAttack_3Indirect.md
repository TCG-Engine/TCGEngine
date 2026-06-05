# JTL_149 Red Squadron Y-Wing — On Attack: 3 indirect to the defending player. Power 1, attacking the
# base: 1 combat + 3 indirect = 4 to P2's base.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_149:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:4
