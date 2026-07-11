# SHD_122 — if the defender survives (a Shield absorbs the hit), it isn't defeated, so nothing is stolen.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:1
P1RESCOUNT:2
