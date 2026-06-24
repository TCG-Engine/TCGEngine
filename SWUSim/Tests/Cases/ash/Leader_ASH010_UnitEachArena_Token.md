# ASH_010 Bo-Katan Kryze — Leader Action [2 resources, Exhaust]: if you control a unit in each arena, create
# a Mandalorian token. P1 has SOR_095 (ground) and SOR_237 (space), so a Mandalorian token (ASH_T01, ground)
# is created — the ground arena goes to 2 units; Bo-Katan exhausts and 2 resources are spent.
## GIVEN
P1LeaderBase: ASH_010/SOR_024
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENACOUNT:2
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
