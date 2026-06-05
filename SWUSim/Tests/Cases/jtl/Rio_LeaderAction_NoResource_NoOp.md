# JTL_015 Rio Durant (leader) — the action costs 1 resource. With 0 ready resources it is a full no-op:
# Rio stays READY, the space unit does not attack, and no decision is pending.

## GIVEN
P1LeaderBase: JTL_015/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1Resources: 0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
P2BASEDMG:0
P1SPACEARENAUNIT:0:READY
