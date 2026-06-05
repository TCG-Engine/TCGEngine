# SOR_015 Boba Fett (leader) — "leaves play" is broader than "defeated": a BOUNCE counts too.
# P1 plays Waylay (SOR_222) to return P2's only unit to hand; that enemy leaving play triggers
# Boba's always-yes reaction → exhaust the leader, ready a resource. P1 has 3 ready (spent on
# Waylay) + 1 exhausted; after Waylay all 4 are exhausted, then Boba readies one back to 1 ready.

## GIVEN
P1LeaderBase: SOR_015/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_222
WithP1Resources: 3:SOR_128:1,1:SOR_128:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1
