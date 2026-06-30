# SEC_002 Jabba the Hutt (leader) — the action needs a friendly damaged unit AND an enemy unit. With a
# friendly damaged unit but NO enemy unit, the action is unaffordable: leader stays READY, resources
# unspent, no decision pending (the player keeps their action).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SEC_080:1:1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESAVAILABLE:2
P1NODECISION
