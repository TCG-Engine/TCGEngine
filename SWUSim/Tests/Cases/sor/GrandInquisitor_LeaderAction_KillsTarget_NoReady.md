# SOR_011 Grand Inquisitor — "Deal 2 damage to a friendly unit with 3 or less power and ready it."
# If the 2 damage DEFEATS the chosen unit (a 3/1), there's nothing left to ready — the unit is gone,
# no crash, leader still pays its exhaust.

## GIVEN
P1LeaderBase: SOR_011/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_180:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED
