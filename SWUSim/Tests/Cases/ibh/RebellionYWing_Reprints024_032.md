# IBH_024 / IBH_032 Rebellion Y-Wing (reprints of IBH_006) — On Attack: deal 1 to a base. Two reprints,
#   each attacks the enemy base directly; base takes combat (2) + On Attack (1) = 3.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: IBH_024:1:0

## WHEN
- P1>AttackSpaceArena:0:theirBase-0

## EXPECT
P2BASEDMG:3
P1NODECISION
