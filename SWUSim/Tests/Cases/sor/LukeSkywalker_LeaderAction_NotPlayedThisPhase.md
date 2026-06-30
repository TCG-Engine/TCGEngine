# SOR_005 Luke Skywalker — Leader Action: No shield when unit not played this phase.
# SOR_095 is pre-existing (GIVEN), not played this phase — no valid targets.

## GIVEN
CommonSetup: gbw/grw/{myResources:1}
WithP1GroundArena: SOR_095:2:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0