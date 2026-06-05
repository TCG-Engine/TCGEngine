# SOR_006 Emperor Palpatine — Leader Action costs [1 resource, exhaust, defeat a friendly
# unit]. With 8 resources but no friendly unit, the defeat-a-friendly-unit cost cannot be
# paid, so the action is a no-op: leader stays ready, no resource spent, nothing queued.

## GIVEN
P1LeaderBase: SOR_006/SOR_024
P2LeaderBase: SOR_007/SOR_024
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESAVAILABLE:8
P1NODECISION
