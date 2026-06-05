# SOR_015 Boba Fett (leader, undeployed, ready) — "When an enemy unit leaves play: You may exhaust
# this leader. If you do, ready a resource." Capture counts as leaving play (CR 8.34).
# P1 plays SHD_131 Take Captive: P1's SOR_095 captures P2's SOR_128. The captured unit leaving play
# triggers Boba's always-yes reaction → Boba auto-exhausts and readies the one exhausted resource.
# Resources: 3 ready + 1 exhausted. After paying cost 3: 0 ready from the main pool.
# Boba fires on capture: readies the 1 exhausted resource → P1RESAVAILABLE becomes 1.
# Both SHD_131 choices auto-picked (single eligible unit each step).
# Assertions: capture happened; Boba is now EXHAUSTED; P1 has 1 ready resource.
# Base: SOR_024 Echo Base (Command aspect) covers SHD_131's Command aspect requirement.

## GIVEN
P1LeaderBase: SOR_015/SOR_024
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_131
WithP1Resources: 3:SOR_128:1,1:SOR_128:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_128
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1
