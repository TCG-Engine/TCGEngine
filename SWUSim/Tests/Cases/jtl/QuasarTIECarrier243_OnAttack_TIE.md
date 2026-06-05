# JTL_243 Quasar TIE Carrier — On Attack: Create a TIE Fighter token. It attacks P2's base and creates
# a TIE.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_243:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:JTL_T01
P2BASEDMG:5
