# IBH_018 Go for the Legs (Event, cost 1, Cunning) — Exhaust an enemy ground unit. Only GROUND is a
#   valid target: with an enemy ground + space unit, the ground one is exhausted (auto-resolves) and
#   the space unit is untouched.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: IBH_018
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:READY
P1NODECISION
