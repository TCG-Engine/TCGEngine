# Twin Suns Phase 4 UI: SWUComputeActionsData reports per-seat counter availability. Before anyone takes a
# counter, both are available to the active seat. After P1 takes blast, P1 reports neither available (took
# a counter this round); the blast counter is globally claimed.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 3

## WHEN
- P1>TakeCounter:blast

## EXPECT
SEATCOUNT:3
BLASTCOUNTER:P1
P1BLASTAVAIL:0
P1PLANAVAIL:0
