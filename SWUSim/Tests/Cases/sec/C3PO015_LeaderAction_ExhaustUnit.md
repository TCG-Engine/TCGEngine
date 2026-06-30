# SEC_015 C-3PO (leader) — Action [1 resource, Exhaust]: If you control an exhausted unit, exhaust a unit.
# P1 controls an exhausted SOR_095 (satisfies the condition) → exhausts the ready enemy SOR_128.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
