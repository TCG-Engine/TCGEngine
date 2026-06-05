# JTL_151 Red Five — with no damaged unit in play, the On Attack offers nothing and Red Five simply
# attacks the base. Proves the "damaged unit" restriction.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_151:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:3
P1NODECISION
