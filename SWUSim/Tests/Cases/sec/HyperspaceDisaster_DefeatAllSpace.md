# SEC_078 Hyperspace Disaster (Event, Vigilance, cost 7) — "Defeat all space units." Both players' space
#   units are defeated; ground units survive.

## GIVEN
CommonSetup: bbk/rrk/{myResources:7}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_078

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1NODECISION
