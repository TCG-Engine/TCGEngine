# SOR_015 Boba Fett (leader, undeployed) — "When an enemy unit leaves play: You may exhaust this
# leader. If you do, ready a resource." Treated as an always-yes auto-resolve: P1's 4/7 defeats
# P2's 3/1, and because P1 has an exhausted resource to ready, Boba auto-exhausts and readies it
# (no prompt).

## GIVEN
P1LeaderBase: SOR_015/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Resources: 1:SOR_128:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1
