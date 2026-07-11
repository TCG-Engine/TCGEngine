# SHD_122 — attacking the base defeats no unit, so nothing is stolen (resource count unchanged).

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:7
P2SPACEARENACOUNT:1
P1RESCOUNT:2
