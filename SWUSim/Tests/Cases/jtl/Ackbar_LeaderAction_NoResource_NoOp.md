# JTL_016 Admiral Ackbar (leader) — the action costs 1 resource. With 0 ready resources it is a full
# no-op: Ackbar stays READY, the enemy unit is not exhausted, no X-Wing is created, and no decision is
# pending.

## GIVEN
P1LeaderBase: JTL_016/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
P2GROUNDARENAUNIT:0:READY
P2SPACEARENACOUNT:0
