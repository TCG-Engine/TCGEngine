# Phase 5 — After first elimination, highest remaining base HP wins at phase end

# P1 and P2 have the same base (equal HP); P2's base is pre-damaged 25, so after P3 is
# eliminated P1 has the strictly-highest remaining HP and wins outright (no tie).

## GIVEN
CommonSetup: grw/grw/{theirBaseDamage:25}
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:3
- P1>ScorePhaseEnd

## EXPECT
GAMEWINNERS:1
