# LAW_014 Enfys Nest (undeployed) — with only 1 ready resource the player can't pay
# the 2-resource cost, so NO reuse offer is made at all (full no-op on the reaction).
# On Attack deals 1 + combat 2 → P2 base = 3; leader stays ready, the resource is kept,
# and there is no dangling decision.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LAW_014;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:3
P1LEADER:READY
P1RESAVAILABLE:1
P1NODECISION
