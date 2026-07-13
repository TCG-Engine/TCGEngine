# Twin Suns Phase 4: turn rotation is clockwise across 3 seats, and the action phase does NOT end after
# only 2 passes (it needs all 3 live players). P1 passes → turn to P2; P2 passes → turn to P3 (streak=2,
# phase still MAIN). If this had used the old 2-consecutive-pass rule, the phase would have ended and
# ActionPhaseStart would have reset TurnPlayer to the initiative holder (1). TURNPLAYER:3 proves it didn't.

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
