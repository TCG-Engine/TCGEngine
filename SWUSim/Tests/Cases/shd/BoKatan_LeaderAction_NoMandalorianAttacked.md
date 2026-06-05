# SHD_012 Bo-Katan Kryze — Leader Action: No Mandalorian attacked → exhaust only, no damage.

## GIVEN
P1LeaderBase: SHD_012/SOR_026
P2LeaderBase: SOR_009/SOR_024
SkipPreGame: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
