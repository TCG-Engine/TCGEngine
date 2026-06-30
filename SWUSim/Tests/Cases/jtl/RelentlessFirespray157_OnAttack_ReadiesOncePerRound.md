# JTL_157 Relentless Firespray — On Attack: Ready this unit (once each round). It attacks P2's base,
# readies, and attacks again (2x4=8 base damage). The second attack's On Attack can't ready it again, so
# it ends exhausted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_157:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:8
P1SPACEARENAUNIT:0:CARDID:JTL_157
P1SPACEARENAUNIT:0:EXHAUSTED
