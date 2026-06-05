# JTL_237 TIE Bomber — On Attack: 3 indirect damage to the defending player. Power 0, so its base attack
# deals no combat damage; the 3 indirect lands on P2's base.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:3
