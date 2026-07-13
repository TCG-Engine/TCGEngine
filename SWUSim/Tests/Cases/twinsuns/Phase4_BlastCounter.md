# Twin Suns Phase 4: the blast counter. P1 takes it → 1 damage to each opponent's base (seat 2 has one;
# seat 3 is base-less here → safe no-op). Taking a counter is recorded (BLASTCOUNTER=P1) and blocks taking
# a second counter this round — so the follow-up TakeCounter:plan is refused and PlanCounter stays AVAILABLE.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 3

## WHEN
- P1>TakeCounter:blast
- P1>TakeCounter:plan

## EXPECT
SEATCOUNT:3
P2BASEDMG:1
BLASTCOUNTER:P1
PLANCOUNTER:AVAILABLE
