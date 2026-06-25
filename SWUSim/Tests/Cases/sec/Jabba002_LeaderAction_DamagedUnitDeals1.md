# SEC_002 Jabba the Hutt (leader) — Action [1 resource, Exhaust]: A friendly damaged unit deals 1
# damage to an enemy unit. (If it has 3+ damage it deals 2 instead — see the other test.)
# Friendly SEC_080 has 1 damage → deals 1 to the only enemy (SOR_095). Both picks auto-resolve.
# Costs 1 resource (2 ready → 1), leader exhausts.

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
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
