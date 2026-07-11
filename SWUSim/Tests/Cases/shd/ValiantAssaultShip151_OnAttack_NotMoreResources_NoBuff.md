# SHD_151 Valiant Assault Ship — when the defending player does NOT control more resources (P1 has 5, P2
# has 1), the +2 does not apply: the base attack deals the printed 3.

## GIVEN
CommonSetup: rrw/rrw/{myResources:5;theirResources:1}
P1OnlyActions: true
WithP1SpaceArena: SHD_151:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:3
