# JTL_003 Lando Calrissian (leader) — the action costs 1 resource. With 0 ready resources the cost
# can't be paid, so the action never starts: Lando stays READY (action not spent), the hand unit is
# not played, and no decision is pending. Unaffordable-cost guard.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_003;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_237
WithP1Resources: 0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
P1HANDCOUNT:1
P1SPACEARENACOUNT:0
