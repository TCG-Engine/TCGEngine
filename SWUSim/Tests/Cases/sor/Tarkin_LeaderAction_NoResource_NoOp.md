# SOR_007 Grand Moff Tarkin — the leader Action costs 1 resource. With 0 ready resources it
# is a full no-op: the leader stays READY (action not spent), the Imperial unit gets no
# Experience, and no decision is pending. Unaffordable-cost guard.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_229:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3
P1NODECISION
