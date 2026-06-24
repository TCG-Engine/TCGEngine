# ASH_010 Bo-Katan Kryze — the token requires a unit in EACH arena. With only a ground unit, no token is
# created (the ground arena stays at 1); the cost is still paid (Bo-Katan exhausts, 2 resources spent).
## GIVEN
P1LeaderBase: ASH_010/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
