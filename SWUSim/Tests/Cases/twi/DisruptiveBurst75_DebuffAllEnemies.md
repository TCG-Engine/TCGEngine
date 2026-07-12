# TWI_075 Disruptive Burst (Event, cost 3, Vigilance) — "Give each enemy unit -1/-1 for this phase."
# SEC_080 (3/3, ground) → 2/2; SOR_237 (2/3, space) → 1/2.

## GIVEN
CommonSetup: bbw/grw/{myResources:3;handCardIds:TWI_075}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2
P2SPACEARENAUNIT:0:POWER:1
P2SPACEARENAUNIT:0:HP:2
