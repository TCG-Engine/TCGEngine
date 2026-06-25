# SEC_002 Jabba the Hutt (leader) — Action [1 resource, Exhaust]: A friendly damaged unit deals damage
# to an enemy unit; if the friendly unit has 3 or more damage on it, it deals 2 instead of 1.
# Friendly LAW_124 (4/7) carries 3 damage → deals 2 to the only enemy (SOR_095, 3/3 survives at 2).
# Proves the 3+-damage → 2 branch (vs the 1 in the sibling test).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: LAW_124:1:3
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
