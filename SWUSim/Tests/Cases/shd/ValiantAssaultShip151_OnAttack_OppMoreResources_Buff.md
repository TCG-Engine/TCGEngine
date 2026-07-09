# SHD_151 Valiant Assault Ship (4-cost 3/4 space) — Saboteur + "On Attack: If the defending player
# controls more resources than you, this unit gets +2/+0 for this attack." P2 controls 5 resources vs P1's
# 1, so the ship gets +2 → 5 power → its base attack deals 5 (proves the +2).

## GIVEN
CommonSetup: rrw/rrw/{myResources:1;theirResources:5}
P1OnlyActions: true
WithP1SpaceArena: SHD_151:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:5
