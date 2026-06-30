# JTL_012 Luke Skywalker (leader) — the damage only happens if you attacked with a Fighter this phase.
# Here P1 never attacked, so the action does nothing (leader still exhausts), nothing is damaged, and no
# decision is pending. Gate test.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1NODECISION
