# SWUSim Replay Schema
Palpatine leader ability — pay 1 resource, defeat friendly, deal 1 damage to a unit

## GIVEN
P1LeaderBase: SOR_006/SOR_024
P2LeaderBase: SOR_007/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1
