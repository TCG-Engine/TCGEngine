# JTL_007 Admiral Holdo (leader) — the action costs 1 resource. With 0 ready resources the cost can't
# be paid: the action never starts, Holdo stays READY, the Resistance unit is not buffed, and no
# decision is pending. Unaffordable-cost guard.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_099:1:0
WithP1Resources: 0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1LEADER:READY
P1NODECISION
