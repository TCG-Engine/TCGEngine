# JTL_070 U-Wing Lander — with no upgrade on it, completing an attack has nothing to move, so no
# decision is offered (a clean fizzle). A friendly Alliance X-Wing is present to prove the no-op is
# from the absent upgrade, not the absent destination.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_070:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P1NODECISION
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:UPGRADECOUNT:0
