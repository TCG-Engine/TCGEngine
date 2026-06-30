# JTL_243 Quasar TIE Carrier — On Attack: Create a TIE Fighter token. It attacks P2's base and creates
# a TIE.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_243:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:JTL_T01
P2BASEDMG:5
