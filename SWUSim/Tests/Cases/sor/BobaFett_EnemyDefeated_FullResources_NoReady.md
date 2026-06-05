# SOR_015 Boba Fett (leader) — the always-yes auto-resolve is SKIPPED when there is no benefit.
# P1's resources are all ready (full), so there is nothing to ready: Boba is NOT exhausted and the
# enemy defeat triggers no resource change.

## GIVEN
P1LeaderBase: SOR_015/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Resources: 1:SOR_128:1

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:READY
P1RESAVAILABLE:1
