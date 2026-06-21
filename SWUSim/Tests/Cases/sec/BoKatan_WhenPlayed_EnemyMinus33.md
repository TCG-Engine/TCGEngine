# SEC_051 Bo-Katan Kryze (Ground, 8/8, cost 9) — When Played: give each enemy unit -3/-3 for this phase.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_051

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:4
P1NODECISION
