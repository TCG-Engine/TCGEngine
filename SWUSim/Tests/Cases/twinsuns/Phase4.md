# BlastCounter
#// Twin Suns Phase 4: the blast counter. P1 takes it → 1 damage to each opponent's base (seat 2 has one;
#// seat 3 is base-less here → safe no-op). Taking a counter is recorded (BLASTCOUNTER=P1) and blocks taking
#// a second counter this round — so the follow-up TakeCounter:plan is refused and PlanCounter stays AVAILABLE.

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

---

# ThreePlayer_Rotation
#// Twin Suns Phase 4: turn rotation is clockwise across 3 seats, and the action phase does NOT end after
#// only 2 passes (it needs all 3 live players). P1 passes → turn to P2; P2 passes → turn to P3 (streak=2,
#// phase still MAIN). If this had used the old 2-consecutive-pass rule, the phase would have ended and
#// ActionPhaseStart would have reset TurnPlayer to the initiative holder (1). TURNPLAYER:3 proves it didn't.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
SEATCOUNT:3
TURNPLAYER:3
