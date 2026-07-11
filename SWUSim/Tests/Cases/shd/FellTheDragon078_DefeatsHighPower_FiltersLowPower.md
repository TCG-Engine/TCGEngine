# SHD_078 Fell the Dragon (4-cost event, Vigilance) — "Defeat a non-leader unit with 5 or more power."
# The enemy SEC_080 carries 2 Experience tokens → 5 power → the only valid target; the 3-power SOR_128 is
# excluded by the filter. With one valid target it auto-resolves (defeats the 5-power unit); SOR_128 survives.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_078
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:1
